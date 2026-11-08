<?php

use App\Enums\ChargeStage;
use App\Enums\PostponementReason;
use App\Enums\ResearcherStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Research\Pages\Charts;
use App\Filament\Research\Widgets\ResearchChart;
use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Services\Research\ChartBuilder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Two courts, two offence categories, hearings in 2025 and 2026; a draft case that must be ignored.
 *
 * @return array{courtA: Court, courtB: Court, korr: Category, dr: Category}
 */
function chartFixture(): array
{
    $courtA = basicCourt(0);
    $courtB = basicCourt(1);
    $korr = Category::query()->where('code', 'KORR')->firstOrFail();
    $dr = Category::query()->where('code', 'DR')->firstOrFail();

    $case = function ($court, array $categories, WorkflowStatus $status = WorkflowStatus::Approved): CourtCase {
        $case = CourtCase::factory()->status($status)->create(['court_id' => $court->id]);
        $party = CaseParty::factory()->create(['case_id' => $case->id]);
        foreach ($categories as $category) {
            Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $category->offences()->value('id'), 'stage' => ChargeStage::Indictment]);
        }

        return $case;
    };

    $hearing = function (CourtCase $case, string $date, bool $postponed = false, bool $late = false, array $reasons = ['defence_lawyer']): void {
        $hearing = Hearing::factory()->forCase($case)->make([
            'hearing_date' => $date, 'was_postponed' => $postponed, 'postponement_reasons' => $postponed ? $reasons : null,
            'started_late' => $late, 'delay_minutes' => $late ? 30 : null, 'is_public' => true,
        ]);
        $hearing->status = WorkflowStatus::Approved;
        $hearing->save();
    };

    $corruptionA = $case($courtA, [$korr]);
    $hearing($corruptionA, '2026-02-10', postponed: true);
    $hearing($corruptionA, '2026-03-10', postponed: true, reasons: ['defence_lawyer', 'expert']);
    $hearing($corruptionA, '2025-05-10');

    $bothA = $case($courtA, [$korr, $dr]); // counts in both categories
    $hearing($bothA, '2026-04-01', postponed: true, late: true);
    $hearing($bothA, '2026-04-20');

    $drugsB = $case($courtB, [$dr]);
    $hearing($drugsB, '2026-01-15', postponed: true);
    $hearing($drugsB, '2025-01-15', late: true);

    $civilB = $case($courtB, []);
    $hearing($civilB, '2026-06-01', postponed: true);

    $draft = $case($courtA, [$korr], WorkflowStatus::Draft);
    $hearing($draft, '2026-02-11', postponed: true);

    return compact('courtA', 'courtB', 'korr', 'dr');
}

/**
 * @param  array<string, mixed>  $result
 * @return array<string, int|float|null> label => value of the first series
 */
function chartValues(array $result, int $series = 0): array
{
    return array_combine($result['labels'], $result['series'][$series]['values']);
}

it('counts postponed hearings in one court and year across the offence categories', function () {
    ['courtA' => $court, 'korr' => $korr, 'dr' => $dr] = chartFixture();

    $result = ChartBuilder::fromArray(['measure' => 'postponed', 'dimension' => 'category', 'year_from' => 2026, 'year_to' => 2026, 'court_id' => $court->id])->build();

    expect(chartValues($result))->toBe([$korr->label() => 3, $dr->label() => 1])
        ->and($result['total'])->toBe(4);
});

it('counts postponed hearings across the categories for all courts and years', function () {
    ['korr' => $korr, 'dr' => $dr] = chartFixture();

    $result = ChartBuilder::fromArray(['measure' => 'postponed', 'dimension' => 'category'])->build();

    expect(chartValues($result))->toBe([$korr->label() => 3, $dr->label() => 2, __('research.charts.none.category') => 1])
        ->and($result['total'])->toBe(8);
});

it('compares along a second dimension and computes shares', function () {
    ['courtA' => $courtA, 'courtB' => $courtB] = chartFixture();

    $result = ChartBuilder::fromArray(['measure' => 'postponement_rate', 'dimension' => 'year', 'compare' => 'court'])->build();
    $byCourt = collect($result['series'])->keyBy('key');

    expect($result['labels'])->toBe(['2025', '2026'])
        ->and($byCourt[(string) $courtA->id]['values'])->toBe([0.0, 75.0])
        ->and($byCourt[(string) $courtB->id]['values'])->toBe([0.0, 100.0])
        ->and($byCourt[(string) $courtA->id]['bases'])->toBe([1, 4]);
});

it('fills the gaps between months and splits postponements by reason', function () {
    chartFixture();

    $months = ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => 'month', 'year_from' => 2026])->build();
    expect($months['labels'])->toBe(['01.2026', '02.2026', '03.2026', '04.2026', '05.2026', '06.2026'])
        ->and($months['series'][0]['values'])->toBe([1, 1, 1, 2, 0, 1]);

    $reasons = ChartBuilder::fromArray(['measure' => 'postponed', 'dimension' => 'reason'])->build();
    expect(chartValues($reasons))->toBe([PostponementReason::DefenceLawyer->getLabel() => 5, PostponementReason::Expert->getLabel() => 1]);

    // Reasons only go with postponed hearings.
    expect(ChartBuilder::fromArray(['measure' => 'late_rate', 'dimension' => 'reason'])->build()['measure'])->toBe('postponed')
        ->and(fn () => new ChartBuilder('late_rate', 'reason'))->toThrow(InvalidArgumentException::class);
});

it('shows the chart builder to approved researchers only, and logs the CSV download', function () {
    ['courtA' => $court] = chartFixture();
    Filament::setCurrentPanel(Filament::getPanel('research'));

    $this->actingAs(researcher(ResearcherStatus::Pending));
    $this->get(Charts::getUrl())->assertForbidden();

    $user = researcher();
    $this->actingAs($user);
    $this->get(Charts::getUrl(['measure' => 'postponed', 'dimension' => 'category', 'court' => $court->id, 'from' => 2026, 'to' => 2026]))
        ->assertOk()
        ->assertSee(__('research.charts.measures.postponed'))
        ->assertSee(Category::query()->where('code', 'KORR')->first()->label());

    Livewire::test(Charts::class)
        ->set('measure', 'late_rate')
        ->set('dimension', 'reason')
        ->assertSet('measure', 'postponed')
        ->set('measure', 'hearings')
        ->assertSet('dimension', 'category')
        ->call('download')
        ->assertFileDownloaded();

    $log = ExportLog::query()->where('resource', 'research:chart')->sole();
    expect($log->user_id)->toBe($user->id)
        ->and($log->context)->toBe(ExportLog::CONTEXT_RESEARCHER)
        ->and($log->filters)->toMatchArray(['measure' => 'hearings', 'dimension' => 'category']);

    Livewire::test(ResearchChart::class, ['result' => ChartBuilder::fromArray([])->build(), 'chartType' => 'line'])->assertOk();
});

it('splits by the traits of the accused and counts the accused per case', function () {
    chartFixture();

    // Four verified cases with one accused each (the draft case is left out).
    $result = ChartBuilder::fromArray(['measure' => 'accused', 'dimension' => 'gender'])->build();

    expect(array_sum($result['series'][0]['values']))->toBe(4)
        ->and($result['unit'])->toBe('accused');

    $counts = ChartBuilder::fromArray(['measure' => 'cases', 'dimension' => 'accused_count'])->build();
    expect(array_sum($counts['series'][0]['values']))->toBe(4);

    $average = ChartBuilder::fromArray(['measure' => 'avg_accused', 'dimension' => 'court'])->build();
    expect($average['series'][0]['values'])->each->toBeFloat();

    foreach (['employment_status', 'economic_condition', 'nationality', 'position', 'counsel', 'role'] as $dimension) {
        expect(ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => $dimension, 'compare' => 'year'])->build()['labels'])->not->toBeEmpty();
    }
});

it('splits by every hearing and case dimension, and late hearings by cause', function () {
    chartFixture();

    foreach (array_diff(ChartBuilder::DIMENSIONS, ['reason', 'delay_cause']) as $dimension) {
        expect(fn () => ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => $dimension])->build())->not->toThrow(Throwable::class);
    }

    $public = ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => 'postponed'])->build();
    expect(chartValues($public))->toBe([__('research.charts.yes') => 5, __('research.charts.no') => 3]);

    // Delay causes only go with the late hearings.
    expect(ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => 'delay_cause'])->build()['measure'])->toBe('late');
});

it('draws every chart type and logs every download format', function () {
    chartFixture();
    Filament::setCurrentPanel(Filament::getPanel('research'));
    $this->actingAs(researcher());

    $result = ChartBuilder::fromArray(['measure' => 'hearings', 'dimension' => 'court', 'compare' => 'year'])->build();
    foreach (['bar', 'horizontal', 'stacked', 'stacked_percent', 'line', 'area', 'pie', 'doughnut', 'polar', 'radar'] as $type) {
        Livewire::test(ResearchChart::class, ['result' => $result, 'chartType' => $type])->assertOk();
    }

    Livewire::test(Charts::class)
        ->set('chartType', 'pie')
        ->assertSee(__('research.charts.download_chart_png'))
        ->call('download', 'xlsx')->assertFileDownloaded()
        ->call('download', 'csv')->assertFileDownloaded()
        ->call('logImage', 'chart')
        ->call('logImage', 'table')
        ->set('chartType', 'table')
        ->assertDontSee('data-chart-download-chart');

    expect(ExportLog::query()->pluck('format')->map(fn ($f) => $f instanceof BackedEnum ? $f->value : $f)->sort()->values()->all())->toBe(['csv', 'png', 'png', 'xlsx']);
});
