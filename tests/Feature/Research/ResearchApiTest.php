<?php

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Yaml\Yaml;

/*
| Researcher API (/api/v1): Sanctum tokens with "research:read", approved researchers
| who accepted the agreement, rate limited, verified data only, every request logged.
*/

/**
 * @return array{0: User, 1: string}
 */
function apiResearcher(ResearcherStatus $status = ResearcherStatus::Approved, bool $agreement = true, array $abilities = ['research:read']): array
{
    $user = researcher($status, $agreement);

    return [$user, $user->createToken('test', $abilities)->plainTextToken];
}

function verifiedCase(array $attributes = []): CourtCase
{
    $case = CourtCase::factory()->create(['court_id' => basicCourt()->id, ...$attributes]);
    $case->forceFill(['status' => WorkflowStatus::Approved, 'approved_at' => now()])->save();
    CaseParty::factory()->create(['case_id' => $case->id]);

    $hearing = Hearing::factory()->forCase($case)->make();
    $hearing->status = WorkflowStatus::Approved;
    $hearing->save();

    return $case->fresh();
}

beforeEach(function () {
    RateLimiter::clear('api');
});

it('rejects requests without a token', function () {
    $this->getJson('/api/v1/cases')->assertUnauthorized();
});

it('rejects invalid and expired tokens', function () {
    $this->withToken('1|not-a-real-token')->getJson('/api/v1/cases')->assertUnauthorized();

    [$user] = apiResearcher();
    $expired = $user->createToken('old', ['research:read'], now()->subDay())->plainTextToken;

    $this->withToken($expired)->getJson('/api/v1/cases')->assertUnauthorized();
});

it('rejects researchers who are not approved or have not accepted the agreement', function (ResearcherStatus $status, bool $agreement) {
    [, $token] = apiResearcher($status, $agreement);

    $this->withToken($token)->getJson('/api/v1/cases')
        ->assertForbidden()
        ->assertJson(['message' => __('research.api.not_approved')]);
})->with([
    'pending' => [ResearcherStatus::Pending, false],
    'approved, agreement missing' => [ResearcherStatus::Approved, false],
    'rejected' => [ResearcherStatus::Rejected, false],
    'suspended' => [ResearcherStatus::Suspended, true],
]);

it('rejects tokens without the research ability', function () {
    [, $token] = apiResearcher(abilities: ['something:else']);

    $this->withToken($token)->getJson('/api/v1/cases')
        ->assertForbidden()
        ->assertJson(['message' => __('research.api.missing_ability')]);
});

it('rejects staff tokens', function () {
    $staff = userWithRole(Role::SuperAdmin);
    $token = $staff->createToken('staff', ['research:read'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/cases')->assertForbidden();
});

it('stops working as soon as a researcher is suspended', function () {
    [$user, $token] = apiResearcher();
    $this->withToken($token)->getJson('/api/v1/cases')->assertOk();

    $user->researcherProfile->update(['status' => ResearcherStatus::Suspended]);
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/cases')->assertForbidden();
});

it('lists only verified cases, anonymized', function () {
    $verified = verifiedCase();
    CourtCase::factory()->create(['court_id' => basicCourt()->id]); // draft
    [, $token] = apiResearcher();

    $response = $this->withToken($token)->getJson('/api/v1/cases')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.ref'))->toBe($verified->public_ref)
        ->and($response->json('data.0.parties.total'))->toBe(1)
        ->and($response->json('data.0.hearings.total'))->toBe(1);

    $keys = array_keys($response->json('data.0'));
    foreach (['id', 'court_case_number', 'indictment_number', 'police_case_number', 'judge', 'judge_id', 'prosecutor', 'monitor', 'summary', 'name'] as $forbidden) {
        expect($keys)->not->toContain($forbidden);
    }
});

it('shows one verified case and hides drafts', function () {
    $verified = verifiedCase();
    $draft = CourtCase::factory()->create(['court_id' => basicCourt()->id]);
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/cases/'.$verified->public_ref)
        ->assertOk()
        ->assertJsonPath('data.ref', $verified->public_ref);

    $this->withToken($token)->getJson('/api/v1/cases/'.$draft->public_ref)->assertNotFound();
});

it('filters cases by type, court and year', function () {
    verifiedCase(['type' => 'civil']);
    $corruption = verifiedCase(['type' => 'criminal_corruption']);
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/cases?type=criminal_corruption')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ref', $corruption->public_ref);

    $this->withToken($token)->getJson('/api/v1/cases?court_id='.basicCourt(1)->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('validates filters', function (string $query) {
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/'.$query)->assertUnprocessable();
})->with([
    'unknown type' => 'cases?type=nonsense',
    'type as array' => 'cases?type[]=civil',
    'court not a number' => 'cases?court_id=abc',
    'page size too large' => 'cases?per_page=500',
    'bad date' => 'hearings?from=31.12.2024',
    'range backwards' => 'hearings?from=2024-12-31&until=2024-01-01',
    'bad statistics date' => 'statistics?until=yesterday',
]);

it('paginates with a bounded page size', function () {
    foreach (range(1, 3) as $i) {
        verifiedCase();
    }
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/cases?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3);
});

it('returns anonymized parties and hearings', function () {
    $case = verifiedCase();
    [, $token] = apiResearcher();

    $party = $this->withToken($token)->getJson('/api/v1/parties')->assertOk()->json('data.0');
    expect($party['id'])->toMatch('/^P-[0-9a-f]{12}$/')
        ->and($party['case_ref'])->toBe($case->public_ref)
        ->and($party)->not->toHaveKey('name');

    $hearing = $this->withToken($token)->getJson('/api/v1/hearings')->assertOk()->json('data.0');
    expect($hearing['case_ref'])->toBe($case->public_ref)
        ->and($hearing)->not->toHaveKeys(['notes', 'security_comment', 'monitor', 'judge']);
});

it('returns suppressed statistics and reference lists', function () {
    verifiedCase();
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/statistics')
        ->assertOk()
        ->assertJsonStructure(['headline' => ['cases', 'hearings', 'postponement_rate'], 'hearings_by_year', 'cases_by_type', 'cases_by_category', 'postponement_by_court', 'postponement_reasons', 'verdicts', 'stage_durations'])
        ->assertJsonPath('headline.cases', null); // 1 case < 5: suppressed

    $this->withToken($token)->getJson('/api/v1/reference/courts')->assertOk()->assertJsonPath('data.0.id', fn ($id) => is_int($id));
    $this->withToken($token)->getJson('/api/v1/reference/offences')->assertOk();
    $this->withToken($token)->getJson('/api/v1/reference/codes')->assertOk()->assertJsonStructure(['case_types', 'stage_types', 'party_roles']);
});

it('labels data in the requested language', function () {
    verifiedCase(['type' => 'civil']);
    [, $token] = apiResearcher();

    $this->withToken($token)->getJson('/api/v1/cases?lang=en')
        ->assertOk()
        ->assertHeader('Content-Language', 'en')
        ->assertJsonPath('data.0.type.value', 'civil')
        ->assertJsonPath('data.0.type.label', __('enums.case_type.civil', [], 'en'));

    $this->withToken($token)->withHeader('Accept-Language', 'sq')->getJson('/api/v1/cases')
        ->assertOk()
        ->assertHeader('Content-Language', 'sq')
        ->assertJsonPath('data.0.type.label', __('enums.case_type.civil', [], 'sq'));
});

it('logs every request that returns records, with the token', function () {
    verifiedCase();
    [$user, $token] = apiResearcher();
    $tokenId = PersonalAccessToken::findToken($token)?->id;

    $this->withToken($token)->getJson('/api/v1/cases?type=criminal_corruption')->assertOk();
    $this->withToken($token)->getJson('/api/v1/parties')->assertOk();
    $this->withToken($token)->getJson('/api/v1/hearings')->assertOk();

    $logs = ExportLog::query()->where('context', ExportLog::CONTEXT_API)->orderBy('id')->get();
    expect($logs->pluck('resource')->all())->toBe(['api:cases', 'api:parties', 'api:hearings'])
        ->and($logs->pluck('user_id')->unique()->all())->toBe([$user->id])
        ->and($logs->pluck('token_id')->unique()->all())->toBe([$tokenId])
        ->and($logs->first()->filters)->toMatchArray(['type' => 'criminal_corruption']);
});

it('rate limits each researcher', function () {
    config(['courtwatch.api.rate_limit_per_minute' => 3]);
    [, $token] = apiResearcher();

    foreach (range(1, 3) as $i) {
        $this->withToken($token)->getJson('/api/v1/reference/codes')->assertOk();
    }

    $this->withToken($token)->getJson('/api/v1/reference/codes')
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

it('is read-only', function () {
    [, $token] = apiResearcher();
    $case = verifiedCase();

    $this->withToken($token)->postJson('/api/v1/cases', [])->assertStatus(405);
    $this->withToken($token)->deleteJson('/api/v1/cases/'.$case->public_ref)->assertStatus(405);

    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/v1')) {
            expect(array_diff($route->methods(), ['GET', 'HEAD']))->toBeEmpty("{$route->uri()} accepts writes");
        }
    }
});

it('publishes OpenAPI documentation for every endpoint', function () {
    $this->get('/docs/api')->assertOk()->assertSee('CourtWatch Researcher API');

    $yaml = (string) $this->get('/docs/api.openapi')->assertOk()->getContent();
    expect($yaml)->not->toContain('{{');

    $spec = Yaml::parse($yaml);
    expect($spec['openapi'])->toBe('3.0.3')
        ->and($spec['servers'][0]['url'])->toBe(rtrim((string) config('app.url'), '/'))
        ->and($spec['components']['securitySchemes']['default']['scheme'])->toBe('bearer');

    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/v1')) {
            expect($spec['paths'])->toHaveKey('/'.$route->uri(), message: 'Regenerate the API docs (composer docs:api): '.$route->uri().' is missing.');
            expect($spec['paths']['/'.$route->uri()]['get']['responses'] ?? [])->not->toBeEmpty('Document a @response for '.$route->uri());
        }
    }

    $this->get('/docs/api.postman')->assertOk()->assertJsonPath('info.name', 'CourtWatch Researcher API');
});
