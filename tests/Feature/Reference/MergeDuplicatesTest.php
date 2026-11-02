<?php

use App\Enums\ChargeStage;
use App\Enums\CourtLevel;
use App\Enums\Role;
use App\Filament\Admin\Resources\Reference\Judges\Pages\ManageJudges;
use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Reference\Court;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Services\Reference\ReferenceMerger;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('re-points every reference to the kept record and soft-deletes duplicates', function () {
    $court = Court::query()->firstOrFail();
    $keep = Judge::query()->create(['name' => 'Arben Krasniqi', 'court_id' => $court->id]);
    $duplicate = Judge::query()->create(['name' => 'ARBEN KRASNIQI', 'court_id' => $court->id]);

    $case = CourtCase::factory()->create(['judge_id' => $duplicate->id, 'court_id' => $court->id]);
    $hearing = Hearing::factory()->forCase($case)->create(['judge_id' => $duplicate->id]);

    $stats = app(ReferenceMerger::class)->merge($keep, [$keep, $duplicate]);

    expect($case->fresh()->judge_id)->toBe($keep->id)
        ->and($hearing->fresh()->judge_id)->toBe($keep->id)
        ->and($duplicate->fresh()->trashed())->toBeTrue()
        ->and($duplicate->fresh()->is_active)->toBeFalse()
        ->and($stats['cases.judge_id'])->toBe(1)
        ->and(Activity::query()->where('event', 'merged')->exists())->toBeTrue();
});

it('drops rows that would break a unique constraint when merging offences', function () {
    $offences = CriminalOffence::query()->limit(2)->get();
    [$keep, $duplicate] = [$offences[0], $offences[1]];
    $party = CaseParty::factory()->create();

    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $keep->id, 'stage' => ChargeStage::Indictment]);
    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $duplicate->id, 'stage' => ChargeStage::Indictment]);
    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $duplicate->id, 'stage' => ChargeStage::Judgment]);

    app(ReferenceMerger::class)->merge($keep, [$duplicate]);

    expect(Charge::query()->where('case_party_id', $party->id)->count())->toBe(2)
        ->and(Charge::query()->where('criminal_offence_id', $duplicate->id)->count())->toBe(0);
});

it('merges monitor court assignments without duplicating pivot rows', function () {
    $courts = Court::query()->where('level', CourtLevel::Basic)->limit(2)->get();
    $keep = Monitor::query()->create(['user_id' => userWithRole(Role::Monitor)->id, 'code' => 'A']);
    $duplicate = Monitor::query()->create(['user_id' => userWithRole(Role::Monitor)->id, 'code' => 'B']);
    $keep->courts()->sync([$courts[0]->id]);
    $duplicate->courts()->sync([$courts[0]->id, $courts[1]->id]);

    app(ReferenceMerger::class)->merge($keep, [$duplicate]);

    expect($keep->courts()->pluck('courts.id')->sort()->values()->all())->toBe($courts->pluck('id')->sort()->values()->all());
});

it('merges from the table bulk action for editors', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $court = Court::query()->firstOrFail();
    $keep = Judge::query()->create(['name' => 'Drita Hoxha', 'court_id' => $court->id]);
    $duplicate = Judge::query()->create(['name' => 'Drita  Hoxha', 'court_id' => $court->id]);
    $case = CourtCase::factory()->create(['judge_id' => $duplicate->id]);

    Livewire::test(ManageJudges::class)
        ->selectTableRecords([$keep->id, $duplicate->id])
        ->callAction(TestAction::make('mergeDuplicates')->table()->bulk(), data: ['keep_id' => $keep->id])
        ->assertHasNoActionErrors();

    expect($case->fresh()->judge_id)->toBe($keep->id)
        ->and($duplicate->fresh()->trashed())->toBeTrue();
});

it('does not offer merging to analysts', function () {
    $this->actingAs(userWithRole(Role::Analyst));

    Livewire::test(ManageJudges::class)->assertActionHidden(TestAction::make('mergeDuplicates')->table()->bulk());
});
