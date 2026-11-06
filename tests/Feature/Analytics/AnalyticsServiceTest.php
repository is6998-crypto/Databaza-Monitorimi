<?php

use App\Enums\CaseType;
use App\Enums\MeasureKind;
use App\Enums\Role;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Widgets\Analytics\AnalyticsOverview;
use App\Filament\Admin\Widgets\Analytics\PostponementByChart;
use App\Filament\Admin\Widgets\Analytics\SeizureConfiscationChart;
use App\Filament\Admin\Widgets\Analytics\StageDurationsChart;
use App\Filament\Admin\Widgets\MyWorkWidget;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Measure;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Two verified cases with known hearings, plus one unverified case that must be ignored.
 */
function analyticsFixture(): array
{
    $courtA = basicCourt(0);
    $courtB = basicCourt(1);

    $caseA = CourtCase::factory()->approved()->type(CaseType::CriminalCorruption)->create(['court_id' => $courtA->id]);
    $caseB = CourtCase::factory()->status(WorkflowStatus::Published)->type(CaseType::CriminalGeneral)->create(['court_id' => $courtB->id]);
    $draft = CourtCase::factory()->type(CaseType::CriminalGeneral)->create(['court_id' => $courtA->id]);

    $hearing = fn (CourtCase $case, array $attributes, WorkflowStatus $status = WorkflowStatus::Approved) => tap(
        Hearing::factory()->forCase($case)->make([
            'was_postponed' => false, 'postponement_reasons' => null, 'started_late' => false, 'is_public' => true,
            'recording' => 'audio', 'interpretation_needed' => false, ...$attributes,
        ]),
        function (Hearing $hearing) use ($status): void {
            $hearing->status = $status;
            $hearing->save();
        },
    );

    $hearing($caseA, ['hearing_date' => '2025-01-10', 'was_postponed' => true, 'postponement_reasons' => ['defence_lawyer', 'expert']]);
    $hearing($caseA, ['hearing_date' => '2025-02-10', 'was_postponed' => true, 'postponement_reasons' => ['defence_lawyer']]);
    $hearing($caseA, ['hearing_date' => '2025-03-10', 'started_late' => true, 'delay_minutes' => 40, 'delay_cause' => 'panel']);
    $hearing($caseB, ['hearing_date' => '2025-03-15', 'is_public' => false, 'recording' => 'none', 'interpretation_needed' => true, 'interpretation_provided' => true]);
    $hearing($caseB, ['hearing_date' => '2025-03-20'], WorkflowStatus::Submitted); // not verified
    $hearing($draft, ['hearing_date' => '2025-03-20', 'was_postponed' => true, 'postponement_reasons' => ['judge']]); // case not verified

    foreach ([[$caseA, '2023-01-01', '2023-07-01', '2025-04-01'], [$caseB, '2023-01-01', '2023-03-02', '2025-04-11']] as [$case, $report, $indictment, $judgment]) {
        CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::CriminalReportFiled, 'date' => $report]);
        CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::IndictmentFiled, 'date' => $indictment]);
        CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::JudgmentAnnounced, 'date' => $judgment, 'outcome' => $case->is($caseA) ? StageOutcome::Convicting : StageOutcome::Acquitting]);
    }

    Measure::query()->create(['case_id' => $caseA->id, 'kind' => MeasureKind::Seizure, 'requested' => true, 'granted' => true, 'requested_on' => '2024-01-01', 'amount_eur' => 1000]);
    Measure::query()->create(['case_id' => $caseA->id, 'kind' => MeasureKind::Confiscation, 'requested' => true, 'granted' => false, 'requested_on' => '2024-01-01']);

    return compact('courtA', 'courtB', 'caseA', 'caseB');
}

it('counts only verified hearings of verified cases', function () {
    analyticsFixture();
    $overview = AnalyticsService::for(new AnalyticsFilters)->overview();

    expect($overview['cases'])->toBe(2)
        ->and($overview['hearings'])->toBe(4)
        ->and($overview['postponed'])->toBe(2)
        ->and($overview['late'])->toBe(1)
        ->and($overview['public'])->toBe(3)
        ->and($overview['judgments'])->toBe(2)
        ->and($overview['convictions'])->toBe(1)
        ->and($overview['avg_delay'])->toBe(40.0);
});

it('breaks postponements down by court, reason and type', function () {
    ['courtA' => $courtA] = analyticsFixture();
    $analytics = AnalyticsService::for(new AnalyticsFilters);

    $byCourt = collect($analytics->postponementBy('court'))->keyBy('key');

    expect($byCourt[(string) $courtA->id]['total'])->toBe(3)
        ->and($byCourt[(string) $courtA->id]['rate'])->toBe(66.7)
        ->and($analytics->postponementReasons()['defence_lawyer'])->toBe(2)
        ->and($analytics->postponementReasons()['expert'])->toBe(1)
        ->and($analytics->postponementReasons()['judge'])->toBe(0)
        ->and(collect($analytics->postponementBy('case_type'))->pluck('key')->all())->toContain('criminal_corruption');
});

it('computes punctuality, openness and compliance', function () {
    analyticsFixture();
    $analytics = AnalyticsService::for(new AnalyticsFilters);

    expect($analytics->punctuality())->toMatchArray(['on_time' => 3, 'late' => 1])
        ->and($analytics->punctuality()['causes']['panel'])->toBe(1)
        ->and($analytics->publicVsClosed())->toBe(['public' => 3, 'closed' => 1])
        ->and($analytics->compliance())->toBe(['recorded' => 3, 'recording_total' => 4, 'interpretation_provided' => 1, 'interpretation_needed' => 1]);
});

it('computes median days between stages and outcomes', function () {
    analyticsFixture();
    $analytics = AnalyticsService::for(new AnalyticsFilters);
    $durations = collect($analytics->stageDurations())->keyBy(fn ($row) => $row['from'].'>'.$row['to']);

    // 181 and 60 days → median 120.5
    expect($durations['criminal_report_filed>indictment_filed']['median'])->toBe(120.5)
        ->and($durations['criminal_report_filed>indictment_filed']['cases'])->toBe(2)
        ->and($analytics->verdicts())->toMatchArray(['convicting' => 1, 'acquitting' => 1])
        ->and($analytics->seizureConfiscation()['seizure'])->toMatchArray(['requested' => 1, 'granted' => 1])
        ->and($analytics->seizureConfiscation()['confiscation'])->toMatchArray(['requested' => 1, 'granted' => 0]);
});

it('applies the date, court and case-type filters', function () {
    ['courtB' => $courtB] = analyticsFixture();

    expect(AnalyticsService::for(AnalyticsFilters::fromArray(['from' => '2025-03-01']))->overview()['hearings'])->toBe(2)
        ->and(AnalyticsService::for(AnalyticsFilters::fromArray(['court_id' => $courtB->id]))->overview()['hearings'])->toBe(1)
        ->and(AnalyticsService::for(AnalyticsFilters::fromArray(['case_type' => 'criminal_corruption']))->overview()['hearings'])->toBe(3)
        ->and(AnalyticsService::for(AnalyticsFilters::fromArray(['until' => '2025-01-31']))->hearingsOverTime())->toBe(['2025-01' => 1]);
});

it('shows the analytics dashboard to analysts and editors, and "my work" to monitors', function () {
    analyticsFixture();

    $this->actingAs(userWithRole(Role::Analyst))
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire(AnalyticsOverview::class)
        ->assertSeeLivewire(PostponementByChart::class)
        ->assertSeeLivewire(SeizureConfiscationChart::class)
        ->assertDontSeeLivewire(MyWorkWidget::class);

    $this->actingAs(monitorUser([basicCourt()->id]))
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire(MyWorkWidget::class)
        ->assertDontSeeLivewire(AnalyticsOverview::class);
});

it('renders the dashboard widgets with the filtered numbers', function () {
    analyticsFixture();
    Filament::setCurrentPanel('admin');
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(AnalyticsOverview::class, ['pageFilters' => ['case_type' => 'criminal_corruption']])
        ->assertSee(__('dashboard.stats.hearings'))
        ->assertSee('66,7 %');

    foreach ([PostponementByChart::class, SeizureConfiscationChart::class, StageDurationsChart::class] as $widget) {
        Livewire::test($widget, ['pageFilters' => []])->assertOk();
    }
});
