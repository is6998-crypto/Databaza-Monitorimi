<?php

namespace App\Services\Portal;

use App\Enums\CaseType;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Anonymizer;

/**
 * Public statistics: verified data only (AnalyticsService) with small-cell suppression
 * (Anonymizer). Never broken down by judge, prosecutor or monitor.
 */
class PortalStatistics
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public static function for(AnalyticsFilters $filters): self
    {
        return new self(AnalyticsService::for($filters));
    }

    /**
     * @return array{cases: int|float|null, hearings: int|float|null, postponement_rate: float|null, late_rate: float|null, public_rate: float|null, conviction_rate: float|null}
     */
    public function headline(): array
    {
        $o = $this->analytics->overview();

        return [
            'cases' => Anonymizer::suppress($o['cases']),
            'hearings' => Anonymizer::suppress($o['hearings']),
            'postponement_rate' => Anonymizer::rate($o['postponed'], $o['hearings']),
            'late_rate' => Anonymizer::rate($o['late'], $o['hearings']),
            'public_rate' => Anonymizer::rate($o['public'], $o['hearings']),
            'conviction_rate' => Anonymizer::rate($o['convictions'], $o['judgments']),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    public function hearingsByYear(): array
    {
        return Anonymizer::suppressAll($this->analytics->hearingsByYear());
    }

    /**
     * @return array<string, int|float|null> translated label => count
     */
    public function casesByType(): array
    {
        $result = [];

        foreach ($this->analytics->casesByType() as $type => $count) {
            $result[CaseType::from($type)->getLabel()] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return list<array{court: string, hearings: int|float|null, postponed: int|float|null, rate: float|null}>
     */
    public function postponementByCourt(): array
    {
        return array_map(fn (array $row): array => [
            'court' => $row['label'],
            'hearings' => Anonymizer::suppress($row['total']),
            'postponed' => Anonymizer::suppress($row['postponed']),
            'rate' => Anonymizer::rate($row['postponed'], $row['total']),
        ], $this->analytics->postponementBy('court', 50));
    }

    /**
     * @return array<string, int|float|null> category label => cases
     */
    public function casesByCategory(): array
    {
        $counts = $this->analytics->casesByCategory();
        $labels = Category::query()->whereIn('id', array_keys($counts))->get()->mapWithKeys(fn (Category $category): array => [(string) $category->id => $category->label()]);

        $result = [];

        foreach ($counts as $id => $count) {
            $result[$labels[$id] ?? '—'] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return array<string, int|float|null>
     */
    public function postponementReasons(): array
    {
        $result = [];

        foreach ($this->analytics->postponementReasons() as $reason => $count) {
            $result[PostponementReason::from($reason)->getLabel()] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return array<string, int|float|null>
     */
    public function verdicts(): array
    {
        $result = [];

        foreach ($this->analytics->verdicts() as $outcome => $count) {
            $result[StageOutcome::from($outcome)->getLabel()] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return list<array{label: string, median: float|int|null, cases: int|float|null}>
     */
    public function stageDurations(): array
    {
        return array_map(fn (array $row): array => [
            'label' => $row['label'],
            'median' => Anonymizer::statistic($row['median'], $row['cases']),
            'cases' => Anonymizer::suppress($row['cases']),
        ], $this->analytics->stageDurations());
    }

    // ------------------------------------------------------------------ open data

    /**
     * @return array<string, string> key => translated title
     */
    public static function datasets(): array
    {
        $keys = ['hearings-by-year-and-court', 'postponement-by-court', 'postponement-reasons', 'cases-by-type', 'cases-by-category', 'verdicts', 'stage-durations'];

        return collect($keys)->mapWithKeys(fn (string $key): array => [$key => __('portal.open_data.datasets.'.$key)])->all();
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float|null>>}
     */
    public function dataset(string $key): array
    {
        $suppressed = fn (int|float|null $value): string|int|float => $value ?? '<'.Anonymizer::threshold();

        return match ($key) {
            'hearings-by-year-and-court' => $this->hearingsByYearAndCourt(),
            'postponement-by-court' => [
                'header' => [__('portal.columns.court'), __('portal.columns.hearings'), __('portal.columns.postponed'), __('portal.columns.rate')],
                'rows' => array_map(fn (array $row): array => [$row['court'], $suppressed($row['hearings']), $suppressed($row['postponed']), $suppressed($row['rate'])], $this->postponementByCourt()),
            ],
            'postponement-reasons' => [
                'header' => [__('portal.columns.reason'), __('portal.columns.count')],
                'rows' => collect($this->postponementReasons())->map(fn ($count, $reason): array => [(string) $reason, $suppressed($count)])->values()->all(),
            ],
            'cases-by-type' => [
                'header' => [__('portal.columns.case_type'), __('portal.columns.cases')],
                'rows' => collect($this->casesByType())->map(fn ($count, $type): array => [(string) $type, $suppressed($count)])->values()->all(),
            ],
            'cases-by-category' => [
                'header' => [__('portal.columns.category'), __('portal.columns.cases')],
                'rows' => collect($this->casesByCategory())->map(fn ($count, $category): array => [(string) $category, $suppressed($count)])->values()->all(),
            ],
            'verdicts' => [
                'header' => [__('portal.columns.outcome'), __('portal.columns.count')],
                'rows' => collect($this->verdicts())->map(fn ($count, $outcome): array => [(string) $outcome, $suppressed($count)])->values()->all(),
            ],
            'stage-durations' => [
                'header' => [__('portal.columns.stages'), __('portal.columns.median_days'), __('portal.columns.cases')],
                'rows' => array_map(fn (array $row): array => [$row['label'], $suppressed($row['median']), $suppressed($row['cases'])], $this->stageDurations()),
            ],
            default => abort(404),
        };
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float|null>>}
     */
    private function hearingsByYearAndCourt(): array
    {
        $courts = Court::selectOptions(false);

        $rows = $this->analytics->hearings()
            ->selectRaw('extract(year from h.hearing_date)::int as year, c.court_id as court, count(*) as total, count(*) filter (where h.was_postponed) as postponed')
            ->groupBy('year', 'court')
            ->orderBy('year')
            ->orderBy('court')
            ->get();

        return [
            'header' => [__('portal.columns.year'), __('portal.columns.court'), __('portal.columns.hearings'), __('portal.columns.postponed'), __('portal.columns.rate')],
            'rows' => $rows->map(fn ($row): array => [
                (int) $row->year,
                (string) ($courts[$row->court] ?? '—'),
                Anonymizer::suppress((int) $row->total) ?? '<'.Anonymizer::threshold(),
                Anonymizer::suppress((int) $row->postponed) ?? '<'.Anonymizer::threshold(),
                Anonymizer::rate((int) $row->postponed, (int) $row->total) ?? '<'.Anonymizer::threshold(),
            ])->values()->all(),
        ];
    }
}
