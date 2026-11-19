<?php

namespace App\Services\Portal;

use App\Enums\CaseType;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use App\Models\Reference\Category;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Anonymizer;

/**
 * The statistics summary of the researcher API (`/api/v1/statistics`): verified data only (AnalyticsService)
 * with small-cell suppression (Anonymizer), never broken down by judge, prosecutor or monitor.
 *
 * The public portal does not use it: it shows only an overview of the monitored cases (PortalOverview).
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
}
