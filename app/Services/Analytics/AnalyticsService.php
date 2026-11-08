<?php

namespace App\Services\Analytics;

use App\Enums\CaseType;
use App\Enums\DelayCause;
use App\Enums\MeasureKind;
use App\Enums\PostponementReason;
use App\Enums\SentenceKind;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\WorkflowStatus;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate indicators for the staff dashboard (proposal B6.6) and the public portal (B6.7).
 *
 * Only verified data is counted: approved or published cases, and approved or published
 * hearings of those cases. The portal applies small-cell suppression on top of these numbers
 * (App\Services\Anonymizer) and never uses the judge dimension.
 */
class AnalyticsService
{
    public function __construct(private readonly AnalyticsFilters $filters = new AnalyticsFilters) {}

    public static function for(AnalyticsFilters $filters): self
    {
        return new self($filters);
    }

    public function filters(): AnalyticsFilters
    {
        return $this->filters;
    }

    // ------------------------------------------------------------------ base queries

    /**
     * Verified, not deleted cases matching the court / case-type filters.
     */
    public function cases(string $alias = 'c'): Builder
    {
        return DB::table("cases as {$alias}")
            ->whereIn("{$alias}.status", WorkflowStatus::verifiedValues())
            ->whereNull("{$alias}.deleted_at")
            ->when($this->filters->courtId, fn (Builder $query, int $id) => $query->where("{$alias}.court_id", $id))
            ->when($this->filters->caseType, fn (Builder $query, CaseType $type) => $query->where("{$alias}.type", $type->value));
    }

    /**
     * Verified hearings of verified cases, within the date range.
     */
    public function hearings(): Builder
    {
        return $this->cases()
            ->join('hearings as h', 'h.case_id', '=', 'c.id')
            ->whereIn('h.status', WorkflowStatus::verifiedValues())
            ->whereNull('h.deleted_at')
            ->when($this->filters->from, fn (Builder $query, Carbon $from) => $query->where('h.hearing_date', '>=', $from->toDateString()))
            ->when($this->filters->until, fn (Builder $query, Carbon $until) => $query->where('h.hearing_date', '<=', $until->toDateString()));
    }

    /**
     * Stages of verified cases, the stage date within the range.
     */
    public function stages(StageType $stage, string $alias = 's'): Builder
    {
        return $this->cases()
            ->join("case_stages as {$alias}", "{$alias}.case_id", '=', 'c.id')
            ->where("{$alias}.stage", $stage->value)
            ->when($this->filters->from, fn (Builder $query, Carbon $from) => $query->where("{$alias}.date", '>=', $from->toDateString()))
            ->when($this->filters->until, fn (Builder $query, Carbon $until) => $query->where("{$alias}.date", '<=', $until->toDateString()));
    }

    // ------------------------------------------------------------------ indicators

    /**
     * Headline numbers.
     *
     * @return array{cases: int, hearings: int, postponed: int, late: int, public: int, judgments: int, convictions: int, avg_delay: float|null}
     */
    public function overview(): array
    {
        $hearings = $this->hearings()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where h.was_postponed) as postponed')
            ->selectRaw('count(*) filter (where h.started_late) as late')
            ->selectRaw('count(*) filter (where h.is_public) as public')
            ->selectRaw('avg(h.delay_minutes) filter (where h.started_late) as avg_delay')
            ->first();

        $verdicts = $this->verdicts();

        return [
            'cases' => (int) $this->cases()->count(),
            'hearings' => (int) ($hearings->total ?? 0),
            'postponed' => (int) ($hearings->postponed ?? 0),
            'late' => (int) ($hearings->late ?? 0),
            'public' => (int) ($hearings->public ?? 0),
            'judgments' => array_sum($verdicts),
            'convictions' => (int) ($verdicts[StageOutcome::Convicting->value] ?? 0),
            'avg_delay' => isset($hearings->avg_delay) ? round((float) $hearings->avg_delay, 1) : null,
        ];
    }

    /**
     * Monitored hearings per month.
     *
     * @return array<string, int> "YYYY-MM" => count, without gaps
     */
    public function hearingsOverTime(): array
    {
        $rows = $this->hearings()
            ->selectRaw("to_char(date_trunc('month', h.hearing_date), 'YYYY-MM') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        if ($rows->isEmpty()) {
            return [];
        }

        $series = [];
        $cursor = Carbon::createFromFormat('Y-m', (string) $rows->keys()->first())->startOfMonth();
        $last = Carbon::createFromFormat('Y-m', (string) $rows->keys()->last())->startOfMonth();

        while ($cursor->lte($last)) {
            $key = $cursor->format('Y-m');
            $series[$key] = (int) ($rows[$key] ?? 0);
            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * Hearings per year (used by the public portal).
     *
     * @return array<string, int>
     */
    public function hearingsByYear(): array
    {
        return $this->hearings()
            ->selectRaw('extract(year from h.hearing_date)::int as year, count(*) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total', 'year')
            ->mapWithKeys(fn ($total, $year): array => [(string) $year => (int) $total])
            ->all();
    }

    /**
     * Postponement rate by court, judge or case type.
     *
     * @return list<array{key: string, label: string, total: int, postponed: int, rate: float}>
     */
    public function postponementBy(string $dimension, int $limit = 15): array
    {
        $column = match ($dimension) {
            'judge' => 'h.judge_id',
            'case_type' => 'c.type',
            default => 'c.court_id',
        };

        $rows = $this->hearings()
            ->whereNotNull($column)
            ->selectRaw("{$column} as dimension, count(*) as total, count(*) filter (where h.was_postponed) as postponed")
            ->groupBy('dimension')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $labels = match ($dimension) {
            'judge' => Judge::query()->withTrashed()->whereIn('id', $rows->pluck('dimension'))->pluck('name', 'id')->all(),
            'case_type' => CaseType::options(),
            default => Court::selectOptions(false),
        };

        return $rows->map(fn ($row): array => [
            'key' => (string) $row->dimension,
            'label' => (string) ($labels[$row->dimension] ?? $row->dimension),
            'total' => (int) $row->total,
            'postponed' => (int) $row->postponed,
            'rate' => $row->total > 0 ? round($row->postponed / $row->total * 100, 1) : 0.0,
        ])->values()->all();
    }

    /**
     * How often each postponement reason was given.
     *
     * @return array<string, int>
     */
    public function postponementReasons(): array
    {
        $rows = $this->hearings()
            ->where('h.was_postponed', true)
            ->crossJoin(DB::raw('jsonb_array_elements_text(h.postponement_reasons) as reason'))
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason');

        return $this->ordered(PostponementReason::values(), $rows->all());
    }

    /**
     * Punctuality: on time vs more than 15 minutes late, and the causes of delays.
     *
     * @return array{on_time: int, late: int, causes: array<string, int>, avg_delay: float|null}
     */
    public function punctuality(): array
    {
        $totals = $this->hearings()
            ->selectRaw('count(*) filter (where not h.started_late) as on_time, count(*) filter (where h.started_late) as late, avg(h.delay_minutes) filter (where h.started_late) as avg_delay')
            ->first();

        $causes = $this->hearings()
            ->where('h.started_late', true)
            ->whereNotNull('h.delay_causes')
            ->crossJoin(DB::raw('jsonb_array_elements_text(h.delay_causes) as cause'))
            ->selectRaw('cause, count(*) as total')
            ->groupBy('cause')
            ->pluck('total', 'cause');

        return [
            'on_time' => (int) ($totals->on_time ?? 0),
            'late' => (int) ($totals->late ?? 0),
            'causes' => $this->ordered(DelayCause::values(), $causes->all()),
            'avg_delay' => isset($totals->avg_delay) ? round((float) $totals->avg_delay, 1) : null,
        ];
    }

    /**
     * @return array{public: int, closed: int}
     */
    public function publicVsClosed(): array
    {
        $row = $this->hearings()
            ->whereNotNull('h.is_public')
            ->selectRaw('count(*) filter (where h.is_public) as public, count(*) filter (where not h.is_public) as closed')
            ->first();

        return ['public' => (int) ($row->public ?? 0), 'closed' => (int) ($row->closed ?? 0)];
    }

    /**
     * Recording and interpretation compliance.
     *
     * @return array{recorded: int, recording_total: int, interpretation_provided: int, interpretation_needed: int}
     */
    public function compliance(): array
    {
        $row = $this->hearings()
            ->selectRaw("count(*) filter (where h.recording is not null and h.recording <> 'none') as recorded")
            ->selectRaw('count(*) filter (where h.recording is not null) as recording_total')
            ->selectRaw('count(*) filter (where h.interpretation_needed and h.interpretation_provided) as interpretation_provided')
            ->selectRaw('count(*) filter (where h.interpretation_needed) as interpretation_needed')
            ->first();

        return [
            'recorded' => (int) ($row->recorded ?? 0),
            'recording_total' => (int) ($row->recording_total ?? 0),
            'interpretation_provided' => (int) ($row->interpretation_provided ?? 0),
            'interpretation_needed' => (int) ($row->interpretation_needed ?? 0),
        ];
    }

    /**
     * Median number of days between key stages.
     *
     * @return list<array{from: string, to: string, label: string, median: float|null, cases: int}>
     */
    public function stageDurations(): array
    {
        $pairs = [
            [StageType::CriminalReportFiled, StageType::IndictmentFiled],
            [StageType::IndictmentFiled, StageType::InitialHearing],
            [StageType::InitialHearing, StageType::JudgmentAnnounced],
            [StageType::JudgmentAnnounced, StageType::JudgmentDrafted],
            [StageType::AppealFiled, StageType::AppealDecision],
            [StageType::CriminalReportFiled, StageType::JudgmentAnnounced],
        ];

        $results = [];

        foreach ($pairs as [$from, $to]) {
            $row = $this->stages($to, 'b')
                ->join('case_stages as a', fn ($join) => $join->on('a.case_id', '=', 'b.case_id')->where('a.stage', '=', $from->value))
                ->whereNotNull('a.date')
                ->whereNotNull('b.date')
                ->selectRaw('percentile_cont(0.5) within group (order by (b.date - a.date)) as median, count(*) as cases')
                ->first();

            $results[] = [
                'from' => $from->value,
                'to' => $to->value,
                'label' => $from->getLabel().' → '.$to->getLabel(),
                'median' => isset($row->median) ? round((float) $row->median, 1) : null,
                'cases' => (int) ($row->cases ?? 0),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function indictmentOutcomes(): array
    {
        return $this->outcomesOf(StageType::IndictmentDecision);
    }

    /**
     * Conviction vs acquittal (first-instance judgments).
     *
     * @return array<string, int>
     */
    public function verdicts(): array
    {
        return $this->outcomesOf(StageType::JudgmentAnnounced);
    }

    /**
     * @return array<string, int>
     */
    public function appealOutcomes(): array
    {
        return $this->outcomesOf(StageType::AppealDecision);
    }

    /**
     * @return array{accepted: int, judgments: int}
     */
    public function pleaAgreements(): array
    {
        $accepted = (int) $this->stages(StageType::PleaAgreement)->where('s.outcome', StageOutcome::Accepted->value)->count();

        return ['accepted' => $accepted, 'judgments' => array_sum($this->verdicts())];
    }

    /**
     * Imposed effective prison sentences (first instance), in buckets of months.
     *
     * @return array<string, int>
     */
    public function sentenceDistribution(): array
    {
        $buckets = ['none' => 0, '1-6' => 0, '7-12' => 0, '13-36' => 0, '37-60' => 0, '61+' => 0];

        $rows = $this->stages(StageType::JudgmentAnnounced)
            ->join('sentences as sn', 'sn.case_id', '=', 'c.id')
            ->where('sn.kind', SentenceKind::Imposed->value)
            ->where('sn.instance', 'first_instance')
            ->selectRaw("case
                when coalesce(sn.prison_months, 0) = 0 then 'none'
                when sn.prison_months <= 6 then '1-6'
                when sn.prison_months <= 12 then '7-12'
                when sn.prison_months <= 36 then '13-36'
                when sn.prison_months <= 60 then '37-60'
                else '61+' end as bucket, count(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        foreach ($rows as $bucket => $total) {
            $buckets[(string) $bucket] = (int) $total;
        }

        return $buckets;
    }

    /**
     * Requested vs imposed average prison months (first instance).
     *
     * @return array{requested: float|null, imposed: float|null, suspended: int}
     */
    public function sentenceAverages(): array
    {
        $row = $this->cases()
            ->join('sentences as sn', 'sn.case_id', '=', 'c.id')
            ->where('sn.instance', 'first_instance')
            ->selectRaw("avg(sn.prison_months) filter (where sn.kind = 'requested') as requested")
            ->selectRaw("avg(sn.prison_months) filter (where sn.kind = 'imposed') as imposed")
            ->selectRaw("count(*) filter (where sn.kind = 'imposed' and coalesce(sn.suspended_months, 0) > 0) as suspended")
            ->first();

        return [
            'requested' => isset($row->requested) ? round((float) $row->requested, 1) : null,
            'imposed' => isset($row->imposed) ? round((float) $row->imposed, 1) : null,
            'suspended' => (int) ($row->suspended ?? 0),
        ];
    }

    /**
     * Seizure and confiscation: requested vs granted.
     *
     * @return array<string, array{requested: int, granted: int, amount_requested: float, amount_granted: float}>
     */
    public function seizureConfiscation(): array
    {
        $rows = $this->cases()
            ->join('measures as m', 'm.case_id', '=', 'c.id')
            ->whereIn('m.kind', [MeasureKind::Seizure->value, MeasureKind::Confiscation->value])
            ->when($this->filters->from, fn (Builder $query, Carbon $from) => $query->where('m.requested_on', '>=', $from->toDateString()))
            ->when($this->filters->until, fn (Builder $query, Carbon $until) => $query->where('m.requested_on', '<=', $until->toDateString()))
            ->selectRaw('m.kind as kind')
            ->selectRaw('count(*) filter (where m.requested) as requested')
            ->selectRaw('count(*) filter (where m.granted) as granted')
            ->selectRaw('coalesce(sum(m.amount_eur) filter (where m.requested), 0) as amount_requested')
            ->selectRaw('coalesce(sum(m.amount_eur) filter (where m.granted), 0) as amount_granted')
            ->groupBy('m.kind')
            ->get()
            ->keyBy('kind');

        $result = [];

        foreach ([MeasureKind::Seizure, MeasureKind::Confiscation] as $kind) {
            $row = $rows[$kind->value] ?? null;
            $result[$kind->value] = [
                'requested' => (int) ($row->requested ?? 0),
                'granted' => (int) ($row->granted ?? 0),
                'amount_requested' => (float) ($row->amount_requested ?? 0),
                'amount_granted' => (float) ($row->amount_granted ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Cases per type (portal headline).
     *
     * @return array<string, int>
     */
    public function casesByType(): array
    {
        $rows = $this->cases()->selectRaw('c.type as type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return $this->ordered(CaseType::values(), $rows->all());
    }

    /**
     * Cases per statistics category of the offences charged (a case with offences in two categories counts in both).
     *
     * @return array<string, int> category id => count, largest first
     */
    public function casesByCategory(): array
    {
        return $this->cases()
            ->join('charges as ch', 'ch.case_id', '=', 'c.id')
            ->join('criminal_offences as o', 'o.id', '=', 'ch.criminal_offence_id')
            ->whereNotNull('o.category_id')
            ->selectRaw('o.category_id as category, count(distinct c.id) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->mapWithKeys(fn ($total, $category): array => [(string) $category => (int) $total])
            ->all();
    }

    /**
     * Cases per court (portal).
     *
     * @return array<string, int> court id => count
     */
    public function casesByCourt(): array
    {
        return $this->cases()
            ->whereNotNull('c.court_id')
            ->selectRaw('c.court_id as court, count(*) as total')
            ->groupBy('court')
            ->orderByDesc('total')
            ->pluck('total', 'court')
            ->mapWithKeys(fn ($total, $court): array => [(string) $court => (int) $total])
            ->all();
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @return array<string, int>
     */
    private function outcomesOf(StageType $stage): array
    {
        $rows = $this->stages($stage)
            ->whereNotNull('s.outcome')
            ->selectRaw('s.outcome as outcome, count(*) as total')
            ->groupBy('outcome')
            ->pluck('total', 'outcome');

        return $this->ordered(array_map(fn (StageOutcome $outcome): string => $outcome->value, $stage->outcomes()), $rows->all());
    }

    /**
     * Keeps a stable order (enum order) and fills missing keys with zero.
     *
     * @param  list<string>  $keys
     * @param  array<int|string, mixed>  $values
     * @return array<string, int>
     */
    private function ordered(array $keys, array $values): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = (int) ($values[$key] ?? 0);
        }

        return $result;
    }
}
