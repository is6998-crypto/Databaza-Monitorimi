<?php

namespace App\Services\Research;

use App\Enums\CaseType;
use App\Enums\CounselType;
use App\Enums\DelayCause;
use App\Enums\EconomicCondition;
use App\Enums\Gender;
use App\Enums\HearingType;
use App\Enums\MeasureKind;
use App\Enums\PartyRole;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\Department;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\ReferenceModel;
use App\Models\Reference\SecurityMeasureType;
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
 * A case charged with offences in several categories counts in each of them when split by category (the same for
 * offences and security measures). Splits by a trait of the accused (gender, employment status…) look at the
 * accused only; a hearing or case with accused of several kinds counts once in each.
 */
final class ChartBuilder
{
    public const MEASURES = ['hearings', 'postponed', 'postponement_rate', 'late', 'late_rate', 'avg_delay', 'closed_rate', 'cases', 'accused', 'parties', 'avg_accused'];

    public const DIMENSIONS = [
        // time and place
        'year', 'month', 'court', 'department', 'prosecution_office',
        // the case
        'case_type', 'category', 'offence', 'accused_count', 'security_measure',
        // the hearing
        'hearing_type', 'venue', 'public', 'recording', 'interpretation', 'security', 'phone_used', 'plea_agreement',
        'postponed', 'late', 'reason', 'delay_cause',
        // the parties
        'role', 'gender', 'nationality', 'employment_status', 'economic_condition', 'position', 'counsel',
    ];

    /** Dimension groups, for the select's headings. */
    public const GROUPS = [
        'time_place' => ['year', 'month', 'court', 'department', 'prosecution_office'],
        'case' => ['case_type', 'category', 'offence', 'accused_count', 'security_measure'],
        'hearing' => ['hearing_type', 'venue', 'public', 'recording', 'interpretation', 'security', 'phone_used', 'plea_agreement', 'postponed', 'late', 'reason', 'delay_cause'],
        'parties' => ['role', 'gender', 'nationality', 'employment_status', 'economic_condition', 'position', 'counsel'],
    ];

    /** Traits of the accused (the party join keeps the accused only). */
    public const ACCUSED_DIMENSIONS = ['gender', 'nationality', 'employment_status', 'economic_condition', 'position', 'counsel'];

    /** Yes / no dimensions. */
    public const BOOLEANS = ['public' => 'h.is_public', 'interpretation' => 'h.interpretation_needed', 'security' => 'h.security_adequate', 'phone_used' => 'h.phone_used', 'plea_agreement' => 'h.plea_agreement', 'postponed' => 'h.was_postponed', 'late' => 'h.started_late'];

    /** Measures counted over the parties rather than the hearings. */
    public const PARTY_MEASURES = ['accused', 'parties', 'avg_accused'];

    /** At most this many rows along a category dimension (the largest). */
    public const MAX_ROWS = 40;

    /** Dimensions in time order (shown in order, gaps filled, as lines by default). */
    public const TIME = ['year', 'month'];

    /** Measures that are a share of the hearings (0-100). */
    public const RATES = ['postponement_rate', 'late_rate', 'closed_rate'];

    /** The reason dimension unnests the postponed hearings, so it only goes with their count. */
    public const REASON_MEASURES = ['postponed'];

    /** Dimensions that unnest a list on the hearing: [the only measure they go with, the condition, the column]. */
    public const UNNEST = [
        'reason' => ['postponed', 'h.was_postponed', 'h.postponement_reasons'],
        'delay_cause' => ['late', 'h.started_late', 'h.delay_causes'],
    ];

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

        foreach (self::UNNEST as $list => [$only]) {
            if (in_array($list, [$dimension, $compare], true) && $measure !== $only) {
                throw new InvalidArgumentException('Reasons go only with the hearings they explain.');
            }
        }

        if (count(array_intersect(array_keys(self::UNNEST), [$dimension, $compare])) > 1) {
            throw new InvalidArgumentException('Only one list of reasons at a time.');
        }
    }

    /**
     * The one measure a dimension allows, if it restricts them (reasons go with the hearings they explain).
     */
    public static function onlyMeasure(?string $dimension): ?string
    {
        return self::UNNEST[$dimension][0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $input  measure, dimension, compare and the filters, as the page or a URL gives them
     */
    public static function fromArray(array $input): self
    {
        $measure = in_array($input['measure'] ?? null, self::MEASURES, true) ? $input['measure'] : 'hearings';
        $dimension = in_array($input['dimension'] ?? null, self::DIMENSIONS, true) ? $input['dimension'] : 'year';
        $compare = in_array($input['compare'] ?? null, self::DIMENSIONS, true) && $input['compare'] !== $dimension ? $input['compare'] : null;

        if ($compare !== null && self::onlyMeasure($compare) !== null && self::onlyMeasure($dimension) !== null) {
            $compare = null;
        }

        $measure = self::onlyMeasure($dimension) ?? self::onlyMeasure($compare) ?? $measure;

        return new self($measure, $dimension, $compare, array_intersect_key($input, array_flip(['year_from', 'year_to', 'court_id', 'case_type', 'category_id'])));
    }

    /**
     * @return array{
     *     measure: string, dimension: string, compare: string|null, rate: bool,
     *     labels: list<string>,
     *     series: list<array{key: string, label: string, values: list<int|float|null>, bases: list<int>}>,
     *     truncated: bool, rows_truncated: bool, unit: string, total: int
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
            $value = $row->value === null ? null : (in_array($this->measure, [...self::RATES, 'avg_delay', 'avg_accused'], true) ? round((float) $row->value, 1) : (int) $row->value);
            $cells[$this->key($row->s)][$this->key($row->x)] = ['value' => $value, 'base' => (int) $row->base];
        }

        $xKeys = $this->orderedKeys($this->dimension, collect($cells)->flatMap(fn (array $series) => array_keys($series))->unique()->values()->all(), $cells);
        $rowsTruncated = ! in_array($this->dimension, self::TIME, true) && count($xKeys) > self::MAX_ROWS;
        $xKeys = $rowsTruncated ? array_slice($xKeys, 0, self::MAX_ROWS) : $xKeys;
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
            'rows_truncated' => $rowsTruncated,
            'unit' => $this->unit(),
            'total' => (int) (clone $this->base())->selectRaw('count(distinct '.match ($this->unit()) {
                'cases' => 'c.id',
                'accused', 'parties' => 'cp.id',
                default => 'h.id',
            }.') as total')->value('total'),
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

        foreach (self::UNNEST as $list => [, $condition, $column]) {
            if (in_array($list, [$this->dimension, $this->compare], true)) {
                $query->whereRaw($condition)->crossJoin(DB::raw("jsonb_array_elements_text({$column}) as {$list}"));
            }
        }

        if ($this->needsParties()) {
            $accusedOnly = $this->measure !== 'parties' || array_intersect(self::ACCUSED_DIMENSIONS, [$this->dimension, $this->compare]) !== [];
            $query->join('case_parties as cp', function ($join) use ($accusedOnly): void {
                $join->on('cp.case_id', '=', 'c.id')->whereNull('cp.deleted_at');
                if ($accusedOnly) {
                    $join->where('cp.role', PartyRole::Accused->value);
                }
            });
        }

        return $query;
    }

    private function needsParties(): bool
    {
        return in_array($this->measure, self::PARTY_MEASURES, true)
            || array_intersect([...self::ACCUSED_DIMENSIONS, 'role'], [$this->dimension, $this->compare]) !== [];
    }

    /** What the total counts: hearings, cases, accused or parties. */
    private function unit(): string
    {
        return match ($this->measure) {
            'cases', 'avg_accused' => 'cases',
            'accused', 'parties' => $this->measure,
            default => 'hearings',
        };
    }

    private function expression(string $dimension, Builder $query): string
    {
        $alias = 'd_'.$dimension;

        match ($dimension) {
            'category' => $query->leftJoinSub(DB::table('charges as ch')
                ->join('criminal_offences as o', 'o.id', '=', 'ch.criminal_offence_id')
                ->whereNotNull('o.category_id')
                ->select('ch.case_id', 'o.category_id as v')
                ->distinct(), $alias, "{$alias}.case_id", '=', 'c.id'),
            'offence' => $query->leftJoinSub(DB::table('charges as ch')
                ->select('ch.case_id', 'ch.criminal_offence_id as v')
                ->distinct(), $alias, "{$alias}.case_id", '=', 'c.id'),
            'security_measure' => $query->leftJoinSub(DB::table('measures as m')
                ->where('m.kind', MeasureKind::SecurityMeasure->value)
                ->whereNotNull('m.security_measure_type_id')
                ->select('m.case_id', 'm.security_measure_type_id as v')
                ->distinct(), $alias, "{$alias}.case_id", '=', 'c.id'),
            'accused_count' => $query->leftJoinSub(DB::table('case_parties as ap')
                ->where('ap.role', PartyRole::Accused->value)
                ->whereNull('ap.deleted_at')
                ->groupBy('ap.case_id')
                ->selectRaw('ap.case_id, count(*) as n'), $alias, "{$alias}.case_id", '=', 'c.id'),
            default => null,
        };

        if (isset(self::BOOLEANS[$dimension])) {
            $column = self::BOOLEANS[$dimension];

            return "case when {$column} then 'yes' when not {$column} then 'no' end";
        }

        return match ($dimension) {
            'year' => 'extract(year from h.hearing_date)::int',
            'month' => "to_char(h.hearing_date, 'YYYY-MM')",
            'court' => 'c.court_id',
            'department' => 'c.department_id',
            'prosecution_office' => 'c.prosecution_office_id',
            'case_type' => 'c.type',
            'category', 'offence', 'security_measure' => "{$alias}.v",
            'accused_count' => "case when {$alias}.n is null then '0' when {$alias}.n >= 5 then '5+' else {$alias}.n::text end",
            'hearing_type' => 'h.hearing_type',
            'venue' => 'h.venue',
            'recording' => 'h.recording',
            'reason' => 'reason',
            'delay_cause' => 'delay_cause',
            'role' => 'cp.role',
            'gender' => 'cp.gender',
            'nationality' => 'cp.nationality_id',
            'employment_status' => 'cp.economic_status_id',
            'economic_condition' => 'cp.economic_condition',
            'position' => 'cp.defendant_position_id',
            'counsel' => 'cp.counsel_type',
            default => throw new InvalidArgumentException('Unknown dimension.'),
        };
    }

    private function aggregate(): string
    {
        return match ($this->measure) {
            'hearings' => 'count(distinct h.id)',
            'postponed' => 'count(distinct h.id) filter (where h.was_postponed)',
            'postponement_rate' => '100.0 * count(distinct h.id) filter (where h.was_postponed) / nullif(count(distinct h.id), 0)',
            'late' => 'count(distinct h.id) filter (where h.started_late)',
            'late_rate' => '100.0 * count(distinct h.id) filter (where h.started_late) / nullif(count(distinct h.id), 0)',
            'avg_delay' => 'avg(h.delay_minutes) filter (where h.started_late)',
            'closed_rate' => '100.0 * count(distinct h.id) filter (where not h.is_public) / nullif(count(distinct h.id), 0)',
            'cases' => 'count(distinct c.id)',
            'accused', 'parties' => 'count(distinct cp.id)',
            'avg_accused' => '1.0 * count(distinct cp.id) / nullif(count(distinct c.id), 0)',
            default => throw new InvalidArgumentException('Unknown measure.'),
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
            'avg_accused' => 'count(distinct c.id)',
            default => $this->aggregate(),
        };
    }

    private function isCount(): bool
    {
        return in_array($this->measure, ['hearings', 'postponed', 'late', 'cases', 'accused', 'parties'], true);
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

        if ($dimension === 'accused_count') {
            usort($keys, fn (string $a, string $b): int => (int) $a <=> (int) $b);

            return $keys;
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

        $models = [
            'court' => Court::class, 'department' => Department::class, 'category' => Category::class,
            'prosecution_office' => ProsecutionOffice::class, 'offence' => CriminalOffence::class,
            'security_measure' => SecurityMeasureType::class, 'nationality' => Nationality::class,
            'employment_status' => EconomicStatus::class, 'position' => DefendantPosition::class,
        ];

        $names = match (true) {
            isset($models[$dimension]) => $models[$dimension]::query()->withTrashed()->whereIn('id', $ids)->get()
                ->mapWithKeys(fn (ReferenceModel $model): array => [(string) $model->getKey() => $model->label()])->all(),
            isset(self::BOOLEANS[$dimension]) => ['yes' => __('research.charts.yes'), 'no' => __('research.charts.no')],
            $dimension === 'accused_count' => ['0' => __('research.charts.accused_none'), '5+' => __('research.charts.accused_more', ['count' => 5])],
            default => match ($dimension) {
                'case_type' => CaseType::options(),
                'reason' => PostponementReason::options(),
                'delay_cause' => DelayCause::options(),
                'hearing_type' => HearingType::options(),
                'venue' => Venue::options(),
                'recording' => Recording::options(),
                'role' => PartyRole::options(),
                'gender' => Gender::options(),
                'economic_condition' => EconomicCondition::options(),
                'counsel' => CounselType::options(),
                default => [],
            },
        };

        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = match (true) {
                $key === 'none' => __('research.charts.none.'.$dimension) === 'research.charts.none.'.$dimension ? __('research.charts.not_recorded') : __('research.charts.none.'.$dimension),
                $dimension === 'month' => Carbon::createFromFormat('Y-m', $key)->format('m.Y'),
                default => (string) ($names[$key] ?? $key),
            };
        }

        return $labels;
    }
}
