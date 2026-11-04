<?php

use App\Enums\PartyRole;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function hearingPayload(CourtCase $case, array $overrides = []): array
{
    return [
        'client_uuid' => (string) Str::uuid(),
        'case_id' => $case->id,
        'hearing_date' => Carbon::now('Europe/Belgrade')->subDay()->toDateString(),
        'hearing_type' => 'main_trial',
        'venue' => 'courtroom',
        'is_public' => true,
        'started_late' => true,
        'delay_minutes' => 25,
        'delay_cause' => 'panel',
        'was_postponed' => false,
        'recording' => 'audio',
        'interpretation_needed' => false,
        'phone_used' => false,
        'security_adequate' => true,
        'participants' => ['judge', 'prosecutor'],
        'present_party_ids' => [],
        ...$overrides,
    ];
}

it('serves the installable app shell', function () {
    $this->actingAs(monitorUser([basicCourt()->id]));

    $this->get('/m')
        ->assertOk()
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('monitorApp', false);

    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest['start_url'])->toBe('/m')
        ->and($manifest['display'])->toBe('standalone')
        ->and(file_exists(public_path('sw.js')))->toBeTrue()
        ->and(file_exists(public_path('icons/icon-512.png')))->toBeTrue();
});

it('requires login, staff role and 2FA', function () {
    $this->get('/m')->assertRedirect();

    $this->actingAs(userWithRole(Role::Monitor, mfa: false))->get('/m')->assertRedirect('/admin');
    $this->actingAs(userWithRole(Role::Researcher))->get('/m')->assertForbidden();
    $this->actingAs(userWithRole(Role::Analyst))->get('/m')->assertForbidden();
});

it('bootstraps only cases in scope, with party initials instead of names', function () {
    [$mine, $other] = [basicCourt(0), basicCourt(1)];
    $user = monitorUser([$mine->id]);
    $case = CourtCase::factory()->create(['court_id' => $mine->id]);
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Valbona Krasniqi']);
    $hidden = CourtCase::factory()->create(['court_id' => $other->id]);

    $response = $this->actingAs($user)->getJson('/m/api/bootstrap')->assertOk();

    expect(collect($response->json('cases'))->pluck('id')->all())->toContain($case->id)->not->toContain($hidden->id)
        ->and($response->getContent())->not->toContain('Valbona')
        ->and($response->getContent())->not->toContain('Krasniqi')
        ->and($response->json('cases.0.parties.0.label'))->toContain('V. K.');
});

it('syncs an offline hearing once, even when the request is retried', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    $party = CaseParty::factory()->create(['case_id' => $case->id]);
    $foreign = CaseParty::factory()->create();
    $payload = hearingPayload($case, ['present_party_ids' => [$party->id, $foreign->id]]);

    $first = $this->actingAs($user)->postJson('/m/api/hearings', $payload)->assertCreated();
    $second = $this->actingAs($user)->postJson('/m/api/hearings', $payload)->assertOk();

    expect($second->json('id'))->toBe($first->json('id'))
        ->and(Hearing::query()->where('client_uuid', $payload['client_uuid'])->count())->toBe(1);

    $hearing = Hearing::query()->findOrFail($first->json('id'));

    expect($hearing->status)->toBe(WorkflowStatus::Draft)
        ->and($hearing->court_id)->toBe($case->court_id)
        ->and($hearing->judge_id)->toBe($case->judge_id)
        ->and($hearing->monitor_id)->toBe($user->monitor->id)
        ->and($hearing->presentParties()->pluck('case_parties.id')->all())->toBe([$party->id]);
});

it('submits for review and notifies the editors', function () {
    Notification::fake();
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);

    $response = $this->actingAs($user)->postJson('/m/api/hearings', hearingPayload($case, ['submit' => true]))->assertCreated();

    expect($response->json('status'))->toBe(WorkflowStatus::Submitted->value);
    Notification::assertSentTo($editor, WorkflowNotification::class);
});

it('validates synced hearings like the panel form', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);

    $this->actingAs($user)
        ->postJson('/m/api/hearings', hearingPayload($case, [
            'hearing_date' => Carbon::now('Europe/Belgrade')->addDays(3)->toDateString(),
            'was_postponed' => true,
            'postponement_reasons' => [],
            'venue' => 'garden',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hearing_date', 'postponement_reasons', 'venue']);
});

it('refuses hearings for cases outside the monitor scope', function () {
    $user = monitorUser([basicCourt(0)->id]);
    $case = CourtCase::factory()->create(['court_id' => basicCourt(1)->id]);

    $this->actingAs($user)->postJson('/m/api/hearings', hearingPayload($case))->assertForbidden();
});

it('lists legal entities by role and initials', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    CaseParty::factory()->role(PartyRole::LegalEntity)->create(['case_id' => $case->id, 'name' => '"Dardania Projekt" SH.P.K.']);

    $label = $this->actingAs($user)->getJson('/m/api/bootstrap')->json('cases.0.parties.0.label');

    expect($label)->toContain('D. P. S.')->not->toContain('"');
});
