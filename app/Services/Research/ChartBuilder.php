<?php

namespace App\Services\Research;

use App\Enums\CaseType;
use App\Enums\PostponementReason;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Models\Reference\Department;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Anonymizer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The researchers' chart builder: one measure of the monitored hearings (how many were postponed, the share that
 * started late…), narrowed by filters (years, court, case type, offence category), split along one dimension
 * (year, month, court, case type, department, offence category, reason for postponement) and optionally compared
 * along a second one. Verified data only, through AnalyticsService.
 *
 * A case charged with offences in several categories counts in each of them when split by category.
 */
final class ChartBuilder
{
    public const MEASURES = ['hearings', 'postponed', 'postponement_rate', 'late', 'late_rate', 'avg_delay', 'closed_rate', 'cases'];

    public const DIMENSIONS = ['year', 'month', 'court', 'case_type', 'department', 'category', 'reason'];

    /** Dimensions in time order (shown in order, gaps filled, as lines by default). */
    public const TIME = ['year', 'month'];

    /** Measures that are a share of the hearings (0-100). */
    public const RATES = ['postponement_rate', 'late_rate', 'closed_rate'];

    /** The reason dimension unnests the postponed hearings, so it only goes with their count. */
    public const REASON_MEASURES = ['postponed'];

    public const MAX_SERIES = 10;

    /**
     * @param  array{year_from?: int|string|null, year_to?: int|string|null, court_id?: int|string|null, case_type?: string|null, category_id?: int|string|null}  $filters
     */
    public function __construct(
        private readonly string $measure,
        private readonly string $dimension,
        private readonly ?string $compare = null,
        private readonly array $filters = [],
    ) {
        if (! in_array($measure, self::MEASURES, true) || ! in_array($dimension, self::DIMENSIONS, true)) {
            throw new InvalidArgumentException('Unknown measure or dimension.');
        }

        if ($compare !== null && (! in_array($compare, self::DIMENSIONS, true) || $compare === $dimension)) {
            throw new InvalidArgumentException('The comparison must be another dimension.');
        }

        if (in_array('reason', [$dimension, $compare], true) && ! in_array($measure, self::REASON_MEASURES, true)) {
            throw new InvalidArgumentException('Reasons for postponement go only with the number of postponed hearings.');
        }
    }

    /**
     * @param  array<string, mixed>  $input  measure, dimension, compare and the filters, as the page or a URL gives them
     */
    public static function fromArray(array $input): self
    {
        $measure = in_array($input['measure'] ?? null, self::MEASURES, true) ? $input['measure'] : 'hearings';
        $dimension = in_array($input['dimension'] ?? null, self::DIMENSIONS, true) ? $input['dimension'] : 'year';
        $compare = in_array($input['compare'] ?? null, self::DIMENSIONS, true) && $input['compare'] !== $dimension ? $input['compare'] : null;

        if (in_array('reason', [$dimension, $compare], true) && ! in_array($measure, self::REASON_MEASURES, true)) {
            $measure = 'postponed';
        }

        return new self($measure, $dimension, $compare, array_intersect_key($input, array_flip(['year_from', 'year_to', 'court_id', 'case_type', 'category_id'])));
    }

    /**
     * @return array{
     *     measure: string, dimension: string, compare: string|null, rate: bool,
     *     labels: list<string>,
     *     series: list<array{key: string, label: string, values: list<int|float|null>, bases: list<int>}>,
     *     truncated: bool, total: int
     * }
     */
    public function build(): array
    {
        $query = $this->base();
        $x = $this->expression($this->dimension, $query);
        $s = $this->compare ? $this->expression($this->compare, $query) : null;

        $query->selectRaw("{$x} as x")
            ->selectRaw($s ? "{$s} as s" : "'all' as s")
            ->selectRaw($this->aggregate().' as value')
            ->selectRaw($this->baseExpression().' as base')
            ->groupByRaw($s ? "{$x}, {$s}" : $x);

        $cells = [];
        foreach ($query->get() as $row) {
            $value = $row->value === null ? null : (in_array($this->measure, [...self::RATES, 'avg_delay'], true) ? round((float) $row->value, 1) : (int) $row->value);
            $cells[$this->key($row->s)][$this->key($row->x)] = ['value' => $value, 'base' => (int) $row->base];
        }

        $xKeys = $this->orderedKeys($this->dimension, collect($cells)->flatMap(fn (array $series) => array_keys($series))->unique()->values()->all(), $cells);
        $sKeys = $this->compare ? $this->orderedKeys($this->compare, array_map('strval', array_keys($cells)), $this->transpose($cells)) : ['all'];
        $truncated = count($sKeys) > self::MAX_SERIES;
        $sKeys = array_slice($sKeys, 0, self::MAX_SERIES);

        $xLabels = $this->labels($this->dimension, $xKeys);
        $sLabels = $this->compare ? $this->labels($this->compare, $sKeys) : ['all' => __('research.charts.measures.'.$this->measure)];

        return [
            'measure' => $this->measure,
            'dimension' => $this->dimension,
            'compare' => $this->compare,
            'rate' => in_array($this->measure, self::RATES, true),
            'labels' => array_values(array_map(fn (string $key): string => $xLabels[$key], $xKeys)),
            'series' => array_map(fn (string $sKey): array => [
                'key' => $sKey,
                'label' => $sLabels[$sKey],
                'values' => array_map(fn (string $xKey) => $cells[$sKey][$xKey]['value'] ?? ($this->isCount() ? 0 : null), $xKeys),
                'bases' => array_map(fn (string $xKey): int => $cells[$sKey][$xKey]['base'] ?? 0, $xKeys),
            ], $sKeys),
            'truncated' => $truncated,
            'total' => (int) (clone $this->base())->selectRaw('count(distinct '.($this->measure === 'cases' ? 'c.id' : 'h.id').') as total')->value('total'),
        ];
    }

    /**
     * True when a cell rests on fewer cases or hearings than the publication threshold.
     */
    public static function isSmall(int $base): bool
    {
        return $base > 0 && $base < Anonymizer::threshold();
    }

    /**
     * The applied filters, for the export log.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(['measure' => $this->measure, 'dimension' => $this->dimension, 'compare' => $this->compare, ...$this->filters], fn ($value): bool => filled($value));
    }

    // ------------------------------------------------------------------ query

    private function base(): Builder
    {
        $from = is_numeric($this->filters['year_from'] ?? null) ? (int) $this->filters['year_from'] : null;
        $to = is_numeric($this->filters['year_to'] ?? null) ? (int) $this->filters['year_to'] : null;

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $query = AnalyticsService::for(new AnalyticsFilters(
            from: $from !== null ? Carbon::create($from, 1, 1) : null,
            until: $to !== null ? Carbon::create($to, 12, 31) : null,
            courtId: is_numeric($this->filters['court_id'] ?? null) ? (int) $this->filters['court_id'] : null,
            caseType: CaseType::tryFrom((string) ($this->filters['case_type'] ?? '')),
        ))->hearings();

        if (is_numeric($this->filters['category_id'] ?? null)) {
            $query->whereExists(fn (Builder $exists) => $exists->from('charges as fch')
                ->join('criminal_offences as fo', 'fo.id', '=', 'fch.criminal_offence_id')
                ->whereColumn('fch.case_id', 'c.id')
                ->where('fo.category_id', (int) $this->filters['category_id']));
        }

        if (in_array('reason', [$this->dimension, $this->compare], true)) {
            $query->where('h.was_postponed', true)
                ->crossJoin(DB::raw('jsonb_array_elements_text(h.postponement_reasons) as reason'));
        }

        return $query;
    }

    private function expression(string $dimension, Builder $query): string
    {
        if ($dimension === 'category') {
            $categories = DB::table('charges as ch')
                ->join('criminal_offences as o', 'o.id', '=', 'ch.criminal_offence_id')
                ->whereNotNull('o.category_id')
                ->select('ch.case_id', 'o.category_id')
                ->distinct();
            $query->leftJoinSub($categories, 'cc', 'cc.case_id', '=', 'c.id');
        }

        return match ($dimension) {
            'year' => 'extract(year from h.hearing_date)::int',
            'month' => "to_char(h.hearing_date, 'YYYY-MM')",
            'court' => 'c.court_id',
            'case_type' => 'c.type',
            'department' => 'c.department_id',
            'category' => 'cc.category_id',
            'reason' => 'reason',
        };
    }

    private function aggregate(): string
    {
        return match ($this->measure) {
            'hearings' => 'count(distinct h.id)',
            'postponed' => $this->dimension === 'reason' || $this->compare === 'reason' ? 'count(*)' : 'count(distinct h.id) filter (where h.was_postponed)',
            'postponement_rate' => '100.0 * count(distinct h.id) filter (where h.was_postponed) / nullif(count(distinct h.id), 0)',
            'late' => 'count(distinct h.id) filter (where h.started_late)',
            'late_rate' => '100.0 * count(distinct h.id) filter (where h.started_late) / nullif(count(distinct h.id), 0)',
            'avg_delay' => 'avg(h.delay_minutes) filter (where h.started_late)',
            'closed_rate' => '100.0 * count(distinct h.id) filter (where not h.is_public) / nullif(count(distinct h.id), 0)',
            'cases' => 'count(distinct c.id)',
        };
    }

    /**
     * What a cell rests on, for the small-cell mark: the count itself, the hearings of a share, the late hearings
     * of an average delay.
     */
    private function baseExpression(): string
    {
        return match ($this->measure) {
            'avg_delay' => 'count(distinct h.id) filter (where h.started_late)',
            'postponement_rate', 'late_rate', 'closed_rate' => 'count(distinct h.id)',
            default => $this->aggregate(),
        };
    }

    private function isCount(): bool
    {
        return in_array($this->measure, ['hearings', 'postponed', 'late', 'cases'], true);
    }

    // ------------------------------------------------------------------ keys and labels

    private function key(mixed $value): string
    {
        return $value === null ? 'none' : (string) $value;
    }

    /**
     * Time in order with the gaps filled; everything else largest first, "none" last.
     *
     * @param  list<string>  $keys
     * @param  array<string, array<string, array{value: int|float|null, base: int}>>  $cells  series => key => cell
     * @return list<string>
     */
    private function orderedKeys(string $dimension, array $keys, array $cells): array
    {
        if ($keys === []) {
            return [];
        }

        if ($dimension === 'year') {
            $years = array_map('intval', $keys);

            return array_map('strval', range(min($years), max($years)));
        }

        if ($dimension === 'month') {
            sort($keys);
            $cursor = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();
            $last = Carbon::createFromFormat('Y-m', $keys[count($keys) - 1])->startOfMonth();
            $months = [];

            while ($cursor->lte($last)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $months;
        }

        $weight = [];
        foreach ($keys as $key) {
            $weight[$key] = array_sum(array_map(fn (array $series): int => $series[$key]['base'] ?? 0, $cells));
        }

        usort($keys, fn (string $a, string $b): int => [$a === 'none', $weight[$b]] <=> [$b === 'none', $weight[$a]]);

        return $keys;
    }

    /**
     * @param  array<string, array<string, array{value: int|float|null, base: int}>>  $cells
     * @return array<string, array<string, array{value: int|float|null, base: int}>>
     */
    private function transpose(array $cells): array
    {
        $out = [];
        foreach ($cells as $s => $row) {
            foreach ($row as $x => $cell) {
                $out[$x][(string) $s] = $cell;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private function labels(string $dimension, array $keys): array
    {
        $ids = array_values(array_filter($keys, fn (string $key): bool => is_numeric($key)));

        $names = match ($dimension) {
            'court' => Court::query()->withTrashed()->whereIn('id', $ids)->get()->mapWithKeys(fn (Court $court): array => [(string) $court->id => $court->label()])->all(),
            'department' => Department::query()->withTrashed()->whereIn('id', $ids)->get()->mapWithKeys(fn (Department $department): array => [(string) $department->id => $department->label()])->all(),
            'category' => Category::query()->withTrashed()->whereIn('id', $ids)->get()->mapWithKeys(fn (Category $category): array => [(string) $category->id => $category->label()])->all(),
            'case_type' => CaseType::options(),
            'reason' => collect(PostponementReason::cases())->mapWithKeys(fn (PostponementReason $reason): array => [$reason->value => (string) $reason->getLabel()])->all(),
            default => [],
        };

        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = match (true) {
                $key === 'none' => __('research.charts.none.'.$dimension),
                $dimension === 'month' => Carbon::createFromFormat('Y-m', $key)->format('m.Y'),
                default => (string) ($names[$key] ?? $key),
            };
        }

        return $labels;
    }
}
