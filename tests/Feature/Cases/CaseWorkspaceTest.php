<?php

use App\Enums\CaseType;
use App\Enums\ChargeStage;
use App\Enums\PartyRole;
use App\Enums\Role;
use App\Enums\StageType;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\Pages\CreateCourtCase;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Filament\Admin\Resources\Cases\RelationManagers\ChargesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\PartiesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\SentencesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\StagesRelationManager;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Reference\CriminalOffence;
use App\Support\BlindIndex;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function workspace(string $manager, CourtCase $case)
{
    return Livewire::test($manager, ['ownerRecord' => $case, 'pageClass' => ViewCourtCase::class]);
}

it('lets a monitor create a case that is assigned to them', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $this->actingAs($user);

    Livewire::test(CreateCourtCase::class)
        ->fillForm([
            'type' => CaseType::CriminalCorruption->value,
            'court_id' => $court->id,
            'court_case_number' => 'PKR.nr.77/2026',
            'indictment_number' => 'PP/II.nr.12/2026',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $case = CourtCase::query()->where('court_case_number', 'PKR.nr.77/2026')->firstOrFail();

    expect($case->created_by)->toBe($user->id)
        ->and($case->assigned_monitor_id)->toBe($user->monitor->id)
        ->and($case->status)->toBe(WorkflowStatus::Draft)
        ->and($case->public_ref)->not->toBeEmpty();
});

it('warns about a duplicate case number in the same court without blocking', function () {
    $court = basicCourt();
    $this->actingAs(userWithRole(Role::Editor));
    CourtCase::factory()->create(['court_id' => $court->id, 'court_case_number' => 'PKR.nr.5/2025']);

    Livewire::test(CreateCourtCase::class)
        ->fillForm(['type' => CaseType::CriminalGeneral->value, 'court_id' => $court->id, 'court_case_number' => ' pkr.nr.5/2025 '])
        ->assertSee(__('cases.duplicate.heading'))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CourtCase::query()->where('court_id', $court->id)->whereRaw('lower(trim(court_case_number)) = ?', ['pkr.nr.5/2025'])->count())->toBe(2);
});

it('adds parties with encrypted names and a blind index', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $this->actingAs($user);
    $case = CourtCase::factory()->create(['court_id' => $court->id, 'created_by' => $user->id, 'type' => CaseType::CriminalCorruption]);

    workspace(PartiesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: [
            'role' => PartyRole::Accused->value,
            'name' => 'Gëzim Testi',
            'gender' => 'male',
        ])
        ->assertHasNoActionErrors();

    $party = CaseParty::query()->where('case_id', $case->id)->firstOrFail();
    $raw = DB::table('case_parties')->where('id', $party->id)->value('name');

    expect($party->name)->toBe('Gëzim Testi')
        ->and($raw)->not->toContain('Gëzim')
        ->and($party->name_index)->toBe(BlindIndex::fullName('gezim testi'));
});

it('records charges per party, offence and stage, refusing duplicates', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['type' => CaseType::CriminalCorruption]);
    $party = CaseParty::factory()->create(['case_id' => $case->id]);
    $offence = CriminalOffence::query()->firstOrFail();

    $data = ['case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Indictment->value];

    workspace(ChargesRelationManager::class, $case)->callAction(TestAction::make('create')->table(), data: $data)->assertHasNoActionErrors();
    workspace(ChargesRelationManager::class, $case)->callAction(TestAction::make('create')->table(), data: $data)->assertHasActionErrors(['criminal_offence_id']);

    expect($case->charges()->count())->toBe(1);
});

it('keeps stage dates chronological', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['type' => CaseType::CriminalGeneral]);
    CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::CriminalReportFiled, 'date' => '2025-03-10']);

    workspace(StagesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: ['stage' => StageType::InvestigationOpened->value, 'date' => '2025-02-01'])
        ->assertHasActionErrors(['date']);

    workspace(StagesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: ['stage' => StageType::InvestigationOpened->value, 'date' => '2025-04-01'])
        ->assertHasNoActionErrors();

    // A later stage that would now come before an existing one is refused too.
    workspace(StagesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: ['stage' => StageType::IndictmentFiled->value, 'date' => '2025-03-15'])
        ->assertHasActionErrors(['date']);
});

it('rejects negative sentence values', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['type' => CaseType::CriminalSerious]);
    $party = CaseParty::factory()->create(['case_id' => $case->id]);

    workspace(SentencesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: [
            'case_party_id' => $party->id,
            'kind' => 'imposed',
            'instance' => 'first_instance',
            'prison_months' => -3,
            'fine_eur' => -10,
        ])
        ->assertHasActionErrors(['prison_months', 'fine_eur']);
});

it('scopes monitors to their courts and their own cases', function () {
    [$mine, $other] = [basicCourt(0), basicCourt(1)];
    $user = monitorUser([$mine->id]);
    $inCourt = CourtCase::factory()->create(['court_id' => $mine->id]);
    $elsewhere = CourtCase::factory()->create(['court_id' => $other->id]);
    $ownElsewhere = CourtCase::factory()->create(['court_id' => $other->id, 'created_by' => $user->id]);

    $this->actingAs($user);

    $this->get("/admin/cases/{$inCourt->id}")->assertOk();
    $this->get("/admin/cases/{$ownElsewhere->id}")->assertOk();
    $this->get("/admin/cases/{$elsewhere->id}")->assertNotFound();

    expect(CourtCase::query()->visibleTo($user)->pluck('id')->all())
        ->toContain($inCourt->id, $ownElsewhere->id)
        ->not->toContain($elsewhere->id);
});

it('lets monitors edit only their own records while they are editable', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $own = CourtCase::factory()->create(['court_id' => $court->id, 'created_by' => $user->id]);
    $approved = CourtCase::factory()->approved()->create(['court_id' => $court->id, 'created_by' => $user->id]);
    $colleague = CourtCase::factory()->create(['court_id' => $court->id]);

    $this->actingAs($user);

    $this->get("/admin/cases/{$own->id}/edit")->assertOk();
    $this->get("/admin/cases/{$approved->id}/edit")->assertForbidden();
    $this->get("/admin/cases/{$colleague->id}/edit")->assertForbidden();
});

it('never lets monitors or editors delete cases', function (Role $role) {
    $case = CourtCase::factory()->create();
    $user = userWithRole($role);

    expect($user->can('delete', $case))->toBeFalse()
        ->and(userWithRole(Role::SuperAdmin)->can('delete', $case))->toBeTrue();
})->with([Role::Monitor, Role::Editor, Role::Analyst]);

it('keeps analysts read-only', function () {
    $case = CourtCase::factory()->create();
    $this->actingAs(userWithRole(Role::Analyst));

    $this->get("/admin/cases/{$case->id}")->assertOk();
    $this->get('/admin/cases/create')->assertForbidden();
    $this->get("/admin/cases/{$case->id}/edit")->assertForbidden();
});

it('freezes published cases for everyone but the super admin', function () {
    $case = CourtCase::factory()->status(WorkflowStatus::Published)->create();

    expect(userWithRole(Role::Editor)->can('update', $case))->toBeFalse()
        ->and(userWithRole(Role::SuperAdmin)->can('update', $case))->toBeTrue();
});

it('renders every workspace tab', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['type' => CaseType::CriminalCorruption]);
    CaseParty::factory()->create(['case_id' => $case->id]);

    foreach (range(0, 6) as $tab) {
        $this->get("/admin/cases/{$case->id}?relation={$tab}")->assertOk();
    }
});
