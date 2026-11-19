<?php

namespace App\Services\Portal;

use App\Enums\CaseType;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Anonymizer;

/**
 * What the public portal shows: an overview of the monitored cases, and nothing more. How many cases and hearings
 * were monitored, and how they split by year, court, case type and offence category. No finding of the monitoring
 * (postponements, delays, closed hearings, judgments, durations) is published: those stay in the staff panel and
 * the researcher area.
 *
 * Verified data only (AnalyticsService), counts of cases and hearings under 5 suppressed (Anonymizer), never
 * broken down by judge, prosecutor or monitor.
 */
class PortalOverview
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public static function for(AnalyticsFilters $filters): self
    {
        return new self(AnalyticsService::for($filters));
    }

    /**
     * Cases monitored, hearings monitored and the number of courts they were in. The number of courts is a count of
     * public institutions, not of people or cases, so it is not suppressed.
     *
     * @return array{cases: int|null, hearings: int|null, courts: int}
     */
    public function headline(): array
    {
        return [
            'cases' => Anonymizer::suppress((int) $this->analytics->monitoredCases()->count()),
            'hearings' => Anonymizer::suppress((int) $this->analytics->hearings()->count()),
            'courts' => count($this->analytics->monitoredCasesByCourt()),
        ];
    }

    /**
     * Cases monitored per year. The year filter does not narrow this one (it would leave one bar): the chart shows
     * every year and marks the chosen one.
     *
     * @return array<string, int|null> year => cases
     */
    public function casesByYear(): array
    {
        $filters = $this->analytics->filters();

        return Anonymizer::suppressAll(AnalyticsService::for(new AnalyticsFilters(courtId: $filters->courtId, caseType: $filters->caseType))->monitoredCasesByYear());
    }

    /**
     * @return array<string, int|null> court name => cases, largest first
     */
    public function casesByCourt(): array
    {
        $counts = $this->analytics->monitoredCasesByCourt();
        $courts = Court::selectOptions(false);
        $result = [];

        foreach ($counts as $id => $count) {
            $result[$courts[(int) $id] ?? '—'] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return array<string, int|null> translated case type => cases
     */
    public function casesByType(): array
    {
        $result = [];

        foreach ($this->analytics->monitoredCasesByType() as $type => $count) {
            $result[CaseType::from($type)->getLabel()] = Anonymizer::suppress($count);
        }

        return $result;
    }

    /**
     * @return array<string, int|null> category => cases, largest first
     */
    public function casesByCategory(): array
    {
        $counts = $this->analytics->monitoredCasesByCategory();
        $labels = Category::query()->whereIn('id', array_keys($counts))->get()->mapWithKeys(fn (Category $category): array => [(string) $category->id => $category->label()]);
        $result = [];

        foreach ($counts as $id => $count) {
            $result[$labels[$id] ?? '—'] = Anonymizer::suppress($count);
        }

        return $result;
    }

    // ------------------------------------------------------------------ open data

    /**
     * @return array<string, string> key => translated title
     */
    public static function datasets(): array
    {
        $keys = ['cases-by-year-and-court', 'cases-by-court', 'cases-by-type', 'cases-by-category'];

        return collect($keys)->mapWithKeys(fn (string $key): array => [$key => __('portal.open_data.datasets.'.$key)])->all();
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float|null>>}
     */
    public function dataset(string $key): array
    {
        $cell = fn (int|float|null $value): string|int|float => $value ?? '<'.Anonymizer::threshold();
        $pairs = fn (array $rows): array => collect($rows)->map(fn ($count, $label): array => [(string) $label, $cell($count)])->values()->all();

        return match ($key) {
            'cases-by-year-and-court' => $this->casesByYearAndCourt(),
            'cases-by-court' => [
                'header' => [__('portal.columns.court'), __('portal.columns.cases')],
                'rows' => $pairs($this->casesByCourt()),
            ],
            'cases-by-type' => [
                'header' => [__('portal.columns.case_type'), __('portal.columns.cases')],
                'rows' => $pairs($this->casesByType()),
            ],
            'cases-by-category' => [
                'header' => [__('portal.columns.category'), __('portal.columns.cases')],
                'rows' => $pairs($this->casesByCategory()),
            ],
            default => abort(404),
        };
    }

    /**
     * Per year and court: the cases monitored and their monitored hearings.
     *
     * @return array{header: list<string>, rows: list<list<string|int|float|null>>}
     */
    private function casesByYearAndCourt(): array
    {
        $courts = Court::selectOptions(false);
        $cell = fn (int $value): string|int => Anonymizer::suppress($value) ?? '<'.Anonymizer::threshold();

        $rows = $this->analytics->hearings()
            ->selectRaw('extract(year from h.hearing_date)::int as year, c.court_id as court, count(distinct c.id) as cases, count(*) as hearings')
            ->groupBy('year', 'court')
            ->orderBy('year')
            ->orderBy('court')
            ->get();

        return [
            'header' => [__('portal.columns.year'), __('portal.columns.court'), __('portal.columns.cases'), __('portal.columns.hearings')],
            'rows' => $rows->map(fn ($row): array => [
                (int) $row->year,
                (string) ($courts[$row->court] ?? '—'),
                $cell((int) $row->cases),
                $cell((int) $row->hearings),
            ])->values()->all(),
        ];
    }
}
