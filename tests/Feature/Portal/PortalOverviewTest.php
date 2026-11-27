<?php

use App\Enums\CaseType;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\WorkflowStatus;
use App\Livewire\Portal\CaseSearch;
use App\Livewire\Portal\Statistics;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Portal\PortalOverview;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * The public portal shows an overview of the monitored cases only: how many, and by year, court, type and
 * category. No finding of the monitoring (postponements, delays, closed hearings, judgments, durations).
 */
function verifiedHearings(CourtCase $case, array $dates, array $attributes = []): void
{
    foreach ($dates as $date) {
        $hearing = Hearing::factory()->forCase($case)->make(['hearing_date' => $date, ...$attributes]);
        $hearing->status = WorkflowStatus::Approved;
        $hearing->save();
    }
}

/**
 * @return list<CourtCase>
 */
function monitoredCases(Court $court, int $count, CaseType $type = CaseType::CriminalCorruption): array
{
    return collect(range(1, $count))->map(fn () => CourtCase::factory()->approved()->type($type)->create(['court_id' => $court->id]))->all();
}

it('counts the monitored cases, their hearings and the courts', function () {
    $prishtina = basicCourt(0);
    $peja = basicCourt(1);

    foreach (monitoredCases($prishtina, 6) as $case) {
        verifiedHearings($case, ['2025-03-10', '2026-02-10']);
    }

    foreach (monitoredCases($peja, 5, CaseType::Civil) as $case) {
        verifiedHearings($case, ['2026-04-01']);
    }

    // Not verified: never counted.
    $draft = CourtCase::factory()->create(['court_id' => $prishtina->id]);
    verifiedHearings($draft, ['2026-02-10']);

    $overview = PortalOverview::for(new AnalyticsFilters);

    expect($overview->headline())->toBe(['cases' => 11, 'hearings' => 17, 'courts' => 2])
        // A case followed over two years counts in both.
        ->and($overview->casesByYear())->toBe(['2025' => 6, '2026' => 11])
        ->and($overview->casesByCourt())->toBe([$prishtina->label() => 6, $peja->label() => 5])
        ->and($overview->casesByType())->toMatchArray([CaseType::CriminalCorruption->getLabel() => 6, CaseType::Civil->getLabel() => 5]);
});

it('narrows the overview to the cases monitored in the chosen year, and keeps every year on the trend', function () {
    $court = basicCourt();

    foreach (monitoredCases($court, 5) as $case) {
        verifiedHearings($case, ['2024-05-05']);
    }

    foreach (monitoredCases($court, 7) as $case) {
        verifiedHearings($case, ['2026-01-20']);
    }

    $year = PortalOverview::for(new AnalyticsFilters(from: Carbon::create(2026, 1, 1), until: Carbon::create(2026, 12, 31)));

    expect($year->headline())->toBe(['cases' => 7, 'hearings' => 7, 'courts' => 1])
        ->and($year->casesByCourt())->toBe([$court->label() => 7])
        ->and($year->casesByYear())->toBe(['2024' => 5, '2026' => 7]);

    Livewire::test(Statistics::class)
        ->set('year', '2026')
        ->assertViewHas('selectedYear', '2026')
        ->assertSeeHtml('data-stat="cases">7<')
        ->assertSeeHtml('data-stat="courts">1<');
});

it('hides counts under five but not the number of courts', function () {
    $court = basicCourt();
    [$case] = monitoredCases($court, 1);
    verifiedHearings($case, ['2026-03-03', '2026-03-10']);

    $overview = PortalOverview::for(new AnalyticsFilters);

    expect($overview->headline())->toBe(['cases' => null, 'hearings' => null, 'courts' => 1])
        ->and($overview->casesByCourt())->toBe([$court->label() => null])
        ->and($overview->casesByYear())->toBe(['2026' => null]);
});

it('publishes no finding of the monitoring on the portal', function () {
    $court = basicCourt();

    foreach (monitoredCases($court, 6) as $case) {
        verifiedHearings($case, ['2026-02-02', '2026-03-03'], [
            'was_postponed' => true,
            'postponement_reasons' => [PostponementReason::DefenceLawyer->value],
            'started_late' => true,
            'delay_minutes' => 40,
            'is_public' => false,
        ]);
        CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::CriminalReportFiled, 'date' => '2025-01-01']);
        CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::JudgmentAnnounced, 'date' => '2026-04-01', 'outcome' => StageOutcome::Convicting]);
    }

    foreach (['sq', 'en', 'sr'] as $locale) {
        app()->setLocale($locale);

        $html = Livewire::test(Statistics::class)->html();
        $home = (string) $this->get("/{$locale}")->getContent();

        foreach ([$html, $home] as $page) {
            foreach ([
                __('enums.postponement_reason.defence_lawyer'),
                __('enums.stage_outcome.convicting'),
                'data-stat="postponement_rate"', 'data-stat="late_rate"', 'data-stat="public_rate"', 'data-stat="conviction_rate"',
            ] as $finding) {
                expect($page)->not->toContain($finding);
            }
        }

        expect($html)->toContain(__('portal.charts.cases_by_year'))
            ->and($html)->toContain(__('portal.charts.cases_by_court'))
            ->and($html)->toContain(__('portal.charts.cases_by_type'))
            ->and($html)->not->toContain($locale === 'sq' ? 'shtyr' : 'postpone')
            ->and($html)->not->toContain($locale === 'sq' ? 'Mediana' : 'Median');
    }
});

it('offers open data on the monitored cases only, and the former finding tables are gone', function () {
    $court = basicCourt();

    foreach (monitoredCases($court, 5) as $case) {
        verifiedHearings($case, ['2026-02-02']);
    }

    expect(array_keys(PortalOverview::datasets()))->toBe(['cases-by-year-and-court', 'cases-by-court', 'cases-by-type', 'cases-by-category']);

    $csv = (string) $this->get('/en/open-data/cases-by-year-and-court.csv')->assertOk()->getContent();

    expect($csv)->toContain('Year,Court,Cases,Hearings')
        ->and($csv)->toContain('2026,"'.$court->label('en').'",5,5')
        ->and($csv)->not->toContain('Postponed');

    $this->get('/en/open-data/cases-by-court.csv')->assertOk();

    foreach (['hearings-by-year-and-court', 'postponement-by-court', 'postponement-reasons', 'verdicts', 'stage-durations'] as $removed) {
        $this->get("/en/open-data/{$removed}.csv")->assertNotFound();
    }

    $this->get('/en/open-data')->assertOk()->assertDontSee('Postponement')->assertSee('Monitored cases by court');
});

it('still gives approved researchers the full statistics through the API', function () {
    $user = researcher();
    $token = $user->createToken('t', ['research:read'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/statistics')
        ->assertOk()
        ->assertJsonStructure(['headline' => ['postponement_rate', 'late_rate', 'public_rate', 'conviction_rate'], 'postponement_by_court', 'postponement_reasons', 'verdicts', 'stage_durations']);
});

it('shows no findings on the public case cards and case pages either', function () {
    [$case] = monitoredCases(basicCourt(), 1);
    verifiedHearings($case, ['2026-02-02', '2026-03-03', '2026-04-04'], ['was_postponed' => true, 'postponement_reasons' => [PostponementReason::Judge->value], 'started_late' => true, 'delay_minutes' => 30, 'is_public' => false]);
    CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::IndictmentFiled, 'date' => '2025-10-01']);
    CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::JudgmentAnnounced, 'date' => '2026-05-01', 'outcome' => StageOutcome::Acquitting]);

    app()->setLocale('sq');

    $card = Livewire::test(CaseSearch::class)->html();
    $page = (string) $this->get('/sq/cases/'.$case->public_ref)->assertOk()->getContent();

    foreach ([$card, $page] as $html) {
        expect($html)->toContain(StageType::JudgmentAnnounced->getLabel())  // where the case stands: yes
            ->and($html)->not->toContain(StageOutcome::Acquitting->getLabel()) // the verdict: no
            ->and($html)->not->toContain('Të shtyra')
            ->and($html)->not->toContain('Me vonesë');
    }

    // The number of monitored hearings stays.
    expect($page)->toContain('data-hearings-total>3<');

    // There is no filter by judgment any more; an old link with one is simply ignored.
    expect(property_exists(CaseSearch::class, 'outcome'))->toBeFalse();
    $this->get('/sq/cases?outcome=acquitting')->assertOk();
});
