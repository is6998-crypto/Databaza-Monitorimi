<?php

use App\Enums\CaseType;
use App\Enums\PartyRole;
use App\Enums\Role;
use App\Filament\Admin\Resources\Hearings\Pages\CreateHearing;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\FormDraft;
use App\Models\Hearing;
use App\Models\UserRecentValue;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function hearingAnswers(array $overrides = []): array
{
    return [
        'hearing_date' => Carbon::now('Europe/Belgrade')->subDay()->toDateString(),
        'venue' => 'courtroom',
        // Toggle buttons hold 1/0, exactly as the browser sends them.
        'is_public' => 1,
        'started_late' => 0,
        'was_postponed' => 0,
        'phone_used' => 0,
        'interpretation_needed' => 0,
        'security_adequate' => 1,
        ...$overrides,
    ];
}

it('pre-fills court, judge, prosecutor and parties from the case', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id, 'type' => CaseType::CriminalCorruption]);
    $accused = CaseParty::factory()->create(['case_id' => $case->id]);
    CaseParty::factory()->role(PartyRole::InjuredParty)->create(['case_id' => $case->id]);

    $this->actingAs($user);

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->assertSchemaStateSet([
            'case_id' => $case->id,
            'court_id' => $case->court_id,
            'judge_id' => $case->judge_id,
            'prosecutor_id' => $case->prosecutor_id,
            'monitor_id' => $user->monitor->id,
            'presentParties' => [(string) $accused->id],
        ]);
});

it('records a hearing for the case', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    $this->actingAs($user);

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(hearingAnswers(['was_postponed' => 1, 'postponement_reasons' => ['defence_lawyer', 'expert']]))
        ->call('create')
        ->assertHasNoFormErrors();

    $hearing = Hearing::query()->where('case_id', $case->id)->firstOrFail();

    expect($hearing->postponement_reasons)->toBe(['defence_lawyer', 'expert'])
        ->and($hearing->monitor_id)->toBe($user->monitor->id)
        ->and($hearing->created_by)->toBe($user->id)
        ->and(UserRecentValue::query()->where('user_id', $user->id)->where('field', 'court_id')->exists())->toBeTrue();
});

it('refuses hearing dates in the future', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create();

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(hearingAnswers(['hearing_date' => Carbon::now('Europe/Belgrade')->addDays(2)->toDateString()]))
        ->call('create')
        ->assertHasFormErrors(['hearing_date']);
});

it('asks for postponement reasons only when the hearing was postponed', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create();

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(hearingAnswers(['was_postponed' => 1, 'postponement_reasons' => []]))
        ->call('create')
        ->assertHasFormErrors(['postponement_reasons']);

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(hearingAnswers())
        ->assertFormFieldHidden('postponement_reasons')
        ->assertFormFieldHidden('delay_causes')
        ->fillForm(['started_late' => 1])
        ->assertFormFieldVisible('delay_causes');
});

it('clears follow-up answers when the trigger question is answered no', function () {
    $hearing = Hearing::factory()->create(['started_late' => true, 'delay_minutes' => 40, 'delay_causes' => ['panel'], 'was_postponed' => true, 'postponement_reasons' => ['judge']]);

    $hearing->update(['started_late' => false, 'was_postponed' => false]);

    expect($hearing->fresh()->delay_minutes)->toBeNull()
        ->and($hearing->fresh()->delay_causes)->toBeNull()
        ->and($hearing->fresh()->postponement_reasons)->toBeNull();
});

it('autosaves the form as an encrypted draft and restores it', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    $this->actingAs($user);

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(['notes' => 'Shënim i paruajtur'])
        ->call('autosaveDraft')
        ->assertSet('draftSavedAt', fn ($value) => filled($value));

    $draft = FormDraft::query()->where('user_id', $user->id)->firstOrFail();

    expect($draft->data['notes'])->toBe('Shënim i paruajtur')
        ->and(DB::table('form_drafts')->where('id', $draft->id)->value('data'))->not->toContain('paruajtur');

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->assertSchemaStateSet(['notes' => 'Shënim i paruajtur'])
        ->assertSet('draftRestored', true);
});

it('discards the draft once the hearing is saved', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    $this->actingAs($user);

    Livewire::withQueryParams(['case' => $case->id])
        ->test(CreateHearing::class)
        ->fillForm(hearingAnswers())
        ->call('autosaveDraft')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(FormDraft::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('does not let monitors open the hearing form for a case outside their scope', function () {
    $user = monitorUser([basicCourt(0)->id]);
    $case = CourtCase::factory()->create(['court_id' => basicCourt(1)->id]);
    $this->actingAs($user);

    $this->get("/admin/hearings/create?case={$case->id}")->assertForbidden();
});
