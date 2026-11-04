<?php

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Research\Pages\ApiTokens;
use App\Filament\Research\Pages\Datasets;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Services\Research\ResearchQueries;
use App\Support\Pseudonym;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Privacy\PersonalDataFixture;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('research'));
});

/**
 * @return array{0: CourtCase, 1: CourtCase}
 */
function verifiedAndDraftCases(): array
{
    $verified = CourtCase::factory()->create(['court_id' => basicCourt()->id]);
    $verified->forceFill(['status' => WorkflowStatus::Approved, 'approved_at' => now()])->save();
    CaseParty::factory()->create(['case_id' => $verified->id]);
    $hearing = Hearing::factory()->forCase($verified)->make();
    $hearing->status = WorkflowStatus::Approved;
    $hearing->save();

    $draft = CourtCase::factory()->create(['court_id' => basicCourt()->id]);
    CaseParty::factory()->create(['case_id' => $draft->id]);
    Hearing::factory()->forCase($draft)->create();

    return [$verified, $draft];
}

it('offers only verified records to researchers', function () {
    [$verified, $draft] = verifiedAndDraftCases();

    expect(ResearchQueries::cases()->pluck('id')->all())->toBe([$verified->id])
        ->and(ResearchQueries::parties()->get()->pluck('case_id')->unique()->values()->all())->toBe([$verified->id])
        ->and(ResearchQueries::hearings()->get()->pluck('case_id')->unique()->values()->all())->toBe([$verified->id]);
});

it('uses stable pseudonyms that do not reveal the database id', function () {
    expect(Pseudonym::for('party', 42))->toBe(Pseudonym::for('party', 42))
        ->and(Pseudonym::for('party', 42))->not->toBe(Pseudonym::for('party', 43))
        ->and(Pseudonym::for('party', 42))->toMatch('/^P-[0-9a-f]{12}$/')
        ->and(Pseudonym::for('party', 42))->not->toContain('42');
});

it('downloads each anonymized dataset and logs it', function (string $dataset) {
    PersonalDataFixture::create();
    $user = researcher();
    $this->actingAs($user);

    $response = Livewire::test(Datasets::class)
        ->callAction(TestAction::make('download')->arguments(['dataset' => $dataset]), data: ['format' => 'csv'])
        ->assertHasNoActionErrors();

    $log = ExportLog::query()->latest('id')->firstOrFail();
    expect($log->user_id)->toBe($user->id)
        ->and($log->context)->toBe(ExportLog::CONTEXT_RESEARCHER)
        ->and($log->resource)->toBe('research:'.$dataset)
        ->and($log->row_count)->toBeGreaterThan(0);

    // The streamed file itself is checked in the privacy test; the action must not fail.
    expect($response)->not->toBeNull();
})->with(['cases', 'parties', 'hearings']);

it('refuses unknown datasets', function () {
    $this->actingAs(researcher());

    Livewire::test(Datasets::class)
        ->callAction(TestAction::make('download')->arguments(['dataset' => 'users']), data: ['format' => 'csv'])
        ->assertStatus(404);

    expect(ExportLog::query()->count())->toBe(0);
});

it('creates and revokes API tokens with the research ability', function () {
    $user = researcher();
    $this->actingAs($user);

    $page = Livewire::test(ApiTokens::class)
        ->callAction('createToken', data: ['name' => 'Notebook', 'expires_in_days' => 30])
        ->assertHasNoActionErrors();

    $token = $user->tokens()->firstOrFail();
    expect($token->name)->toBe('Notebook')
        ->and($token->abilities)->toBe(['research:read'])
        ->and($token->expires_at?->isFuture())->toBeTrue()
        ->and($token->expires_at?->diffInDays(now()->addDays(30)))->toBeLessThan(1);

    $plain = $page->get('plainTextToken');
    expect($plain)->toBeString()->toContain('|');
    expect(PersonalAccessToken::findToken($plain)?->is($token))->toBeTrue();
    $page->assertSee($plain);

    expect(Activity::query()->where('event', 'token_created')->where('causer_id', $user->id)->exists())->toBeTrue();

    Livewire::test(ApiTokens::class)
        ->assertCanSeeTableRecords([$token])
        ->callAction(TestAction::make('revoke')->table($token))
        ->assertHasNoActionErrors();

    expect($user->tokens()->count())->toBe(0)
        ->and(Activity::query()->where('event', 'token_revoked')->where('causer_id', $user->id)->exists())->toBeTrue();
});

it('lists only the researcher\'s own tokens', function () {
    $other = researcher();
    $otherToken = $other->createToken('theirs', ['research:read'])->accessToken;
    $user = researcher();
    $mine = $user->createToken('mine', ['research:read'])->accessToken;
    $this->actingAs($user);

    Livewire::test(ApiTokens::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$otherToken]);
});

it('does not let pending researchers create tokens', function () {
    $user = researcher(ResearcherStatus::Pending);
    $this->actingAs($user);

    $this->get(ApiTokens::getUrl(panel: 'research'))->assertForbidden();
    expect($user->tokens()->count())->toBe(0);
});

it('does not give staff accounts research tokens', function () {
    $this->actingAs(userWithRole(Role::Analyst));

    $this->get(ApiTokens::getUrl(panel: 'research'))->assertForbidden();
});
