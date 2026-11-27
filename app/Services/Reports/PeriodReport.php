<?php

namespace App\Services\Reports;

use App\Enums\CaseType;
use App\Enums\DeadlineRule;
use App\Enums\DeadlineState;
use App\Enums\DelayCause;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use App\Enums\WorkflowStatus;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Anonymizer;
use App\Services\Documents\Document;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A monitoring report for a period (a quarter, a year…), for funders and partners: what was monitored and what was
 * found, as a Word or PDF document. Only approved and published data is counted (as on the dashboard). Small
 * numbers (under 5) are hidden in the breakdowns unless the staff member chooses to show them.
 */
class PeriodReport
{
    private AnalyticsService $analytics;

    private bool $suppress = true;

    public function make(AnalyticsFilters $filters, bool $suppress = true): Document
    {
        $this->analytics = AnalyticsService::for($filters);
        $this->suppress = $suppress;
        $date = (string) config('courtwatch.date_format');

        $overview = $this->analytics->overview();
        $casesMonitored = (int) $this->analytics->monitoredCases()->count();
        $byCourt = $this->analytics->monitoredCasesByCourt();

        $document = (new Document(__('period_reports.document.title', ['period' => self::periodLabel($filters)]), $this->scope($filters)))
            ->meta(__('period_reports.document.period'), ($filters->from?->format($date) ?? '…').' – '.($filters->until?->format($date) ?? '…'))
            ->meta(__('dashboard.filters.court'), $filters->courtId ? Court::query()->find($filters->courtId)?->label() : __('period_reports.document.all_courts'))
            ->meta(__('dashboard.filters.case_type'), $filters->caseType?->getLabel() ?? __('period_reports.document.all_types'))
            ->meta(__('period_reports.document.source'), __('dashboard.stats.verified_only'))
            ->meta(__('documents.generated'), now((string) config('courtwatch.display_timezone'))->format((string) config('courtwatch.datetime_format')));

        // ---- summary
        $document->heading(__('period_reports.document.summary'))
            ->figures([
                __('period_reports.document.cases_monitored') => $this->n($casesMonitored, always: true),
                __('dashboard.stats.hearings') => $this->n($overview['hearings'], always: true),
                __('period_reports.document.courts') => $this->n(count(array_filter($byCourt)), always: true),
                __('dashboard.stats.postponement_rate') => $this->rate($overview['postponed'], $overview['hearings']),
            ])
            ->figures([
                __('dashboard.stats.late_rate') => $this->rate($overview['late'], $overview['hearings']),
                __('dashboard.stats.public_rate') => $this->rate($overview['public'], $overview['hearings']),
                __('period_reports.document.judgments') => $this->n($overview['judgments']),
                __('dashboard.stats.conviction_rate') => $this->rate($overview['convictions'], $overview['judgments']),
            ]);

        // ---- what was monitored
        $postponementByCourt = collect($this->analytics->postponementBy('court', 100))->keyBy('key');
        $courtNames = Court::selectOptions(false);

        $document->heading(__('period_reports.document.by_court'))->table(
            [__('cases.fields.court'), __('period_reports.document.cases'), __('dashboard.stats.hearings'), __('dashboard.charts.postponement_rate_percent')],
            collect($byCourt)->map(fn (int $count, int|string $courtId): array => [
                (string) ($courtNames[$courtId] ?? $courtId),
                $this->n($count),
                $this->n($postponementByCourt->get((string) $courtId)['total'] ?? 0),
                $this->rate($postponementByCourt->get((string) $courtId)['postponed'] ?? 0, $postponementByCourt->get((string) $courtId)['total'] ?? 0),
            ])->values()->all(),
            __('period_reports.document.none'),
        );

        $document->heading(__('period_reports.document.by_type'))->table(
            [__('cases.fields.type'), __('period_reports.document.cases')],
            $this->countRows($this->analytics->monitoredCasesByType(), fn (string $type): string => CaseType::from($type)->getLabel()),
            __('period_reports.document.none'),
        );

        $categories = Category::query()->withTrashed()->get()->mapWithKeys(fn (Category $category): array => [$category->id => $category->label()]);
        $document->heading(__('dashboard.charts.cases_by_category'))->table(
            [__('period_reports.document.category'), __('period_reports.document.cases')],
            $this->countRows($this->analytics->monitoredCasesByCategory(), fn (int|string $id): string => (string) ($categories[$id] ?? $id)),
            __('period_reports.document.none'),
        );

        // ---- how the hearings went
        $reasons = $this->analytics->postponementReasons();
        $document->heading(__('dashboard.charts.postponement_reasons'))
            ->paragraph(__('period_reports.document.postponed_line', ['postponed' => $this->n($overview['postponed']), 'total' => $this->n($overview['hearings'], always: true), 'rate' => $this->rate($overview['postponed'], $overview['hearings'])]))
            ->table(
                [__('period_reports.document.reason'), __('period_reports.document.times'), __('period_reports.document.share')],
                $this->shareRows($reasons, max(1, $overview['postponed']), fn (string $reason): string => PostponementReason::from($reason)->getLabel()),
                __('period_reports.document.none'),
            );

        $punctuality = $this->analytics->punctuality();
        $document->heading(__('dashboard.charts.punctuality'))
            ->paragraph(__('period_reports.document.punctuality_line', [
                'late' => $this->n($punctuality['late']),
                'total' => $this->n($punctuality['on_time'] + $punctuality['late'], always: true),
                'minutes' => $punctuality['avg_delay'] !== null ? number_format($punctuality['avg_delay'], 0) : '—',
            ]))
            ->table(
                [__('dashboard.charts.delay_causes'), __('period_reports.document.times'), __('period_reports.document.share')],
                $this->shareRows($punctuality['causes'], max(1, $punctuality['late']), fn (string $cause): string => DelayCause::from($cause)->getLabel()),
                __('period_reports.document.none'),
            );

        $public = $this->analytics->publicVsClosed();
        $compliance = $this->analytics->compliance();
        $document->heading(__('period_reports.document.openness'))->table(
            [__('period_reports.document.indicator'), __('period_reports.document.count'), __('period_reports.document.share')],
            [
                [__('period_reports.document.public_hearings'), $this->n($public['public']), $this->rate($public['public'], $public['public'] + $public['closed'])],
                [__('period_reports.document.closed_hearings'), $this->n($public['closed']), $this->rate($public['closed'], $public['public'] + $public['closed'])],
                [__('dashboard.charts.compliance_recorded'), $this->n($compliance['recorded']), $this->rate($compliance['recorded'], $compliance['recording_total'])],
                [__('dashboard.charts.compliance_interpretation'), $this->n($compliance['interpretation_provided']), $this->rate($compliance['interpretation_provided'], $compliance['interpretation_needed'])],
            ],
        );

        // ---- decisions
        $document->heading(__('period_reports.document.decisions'));
        foreach ([
            __('dashboard.charts.indictment_outcomes') => $this->analytics->indictmentOutcomes(),
            __('dashboard.charts.verdicts') => $this->analytics->verdicts(),
            __('dashboard.charts.appeals') => $this->analytics->appealOutcomes(),
        ] as $title => $outcomes) {
            $total = array_sum($outcomes);
            $document->table(
                [$title, __('period_reports.document.count'), __('period_reports.document.share')],
                $this->shareRows($outcomes, max(1, $total), fn (string $outcome): string => StageOutcome::from($outcome)->getLabel(), keepZeros: false),
                $title.': '.__('period_reports.document.none'),
            );
        }

        // ---- legal time limits
        $document->heading(__('deadlines.plural'))
            ->paragraph(__('period_reports.document.deadlines_line'), muted: true)
            ->table(
                [__('deadlines.fields.phase'), __('period_reports.document.limits_ending'), __('period_reports.document.exceeded'), __('period_reports.document.share')],
                $this->deadlineRows($filters),
                __('period_reports.document.none'),
            );

        // ---- method
        $document->heading(__('period_reports.document.method'))
            ->paragraph(__('period_reports.document.method_text'))
            ->paragraph($suppress
                ? __('period_reports.document.suppressed', ['threshold' => Anonymizer::threshold()])
                : __('period_reports.document.not_suppressed'), muted: true);

        return $document;
    }

    /**
     * "T3 2026", "2026", "S1 2026", or the two dates.
     */
    public static function periodLabel(AnalyticsFilters $filters): string
    {
        $from = $filters->from;
        $until = $filters->until;
        $date = (string) config('courtwatch.date_format');

        if ($from === null || $until === null) {
            return ($from?->format($date) ?? '…').' – '.($until?->format($date) ?? '…');
        }

        $fullMonths = $from->isSameDay($from->copy()->startOfMonth()) && $until->isSameDay($until->copy()->endOfMonth()->startOfDay());

        if ($fullMonths && $from->year === $until->year) {
            $months = (int) round($from->diffInMonths($until->copy()->addDay()));

            return match (true) {
                $months === 12 => (string) $from->year,
                $months === 6 && in_array($from->month, [1, 7], true) => __('period_reports.half', ['half' => $from->month === 1 ? 1 : 2, 'year' => $from->year]),
                $months === 3 && in_array($from->month, [1, 4, 7, 10], true) => __('period_reports.quarter', ['quarter' => intdiv($from->month - 1, 3) + 1, 'year' => $from->year]),
                default => $from->format($date).' – '.$until->format($date),
            };
        }

        return $from->format($date).' – '.$until->format($date);
    }

    private function scope(AnalyticsFilters $filters): string
    {
        return collect([
            $filters->courtId ? Court::query()->find($filters->courtId)?->label() : __('period_reports.document.all_courts'),
            $filters->caseType?->getLabel(),
        ])->filter()->implode(' · ');
    }

    /**
     * A count for a table; under the threshold it reads "<5" when small numbers are hidden.
     */
    private function n(int $count, bool $always = false): string
    {
        if ($always || ! $this->suppress) {
            return number_format($count, 0, ',', '.');
        }

        return Anonymizer::display(Anonymizer::suppress($count));
    }

    private function rate(int $part, int $total): string
    {
        if ($total === 0) {
            return '—';
        }

        if ($this->suppress) {
            return Anonymizer::display(Anonymizer::rate($part, $total), ' %');
        }

        return number_format($part / $total * 100, 1, ',', '.').' %';
    }

    /**
     * @param  array<int|string, int>  $counts
     * @param  callable(int|string): string  $label
     * @return list<list<string>>
     */
    private function countRows(array $counts, callable $label): array
    {
        return collect($counts)
            ->filter(fn (int $count): bool => $count > 0)
            ->sortDesc()
            ->map(fn (int $count, int|string $key): array => [$label($key), $this->n($count)])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, int>  $counts
     * @param  callable(string): string  $label
     * @return list<list<string>>
     */
    private function shareRows(array $counts, int $total, callable $label, bool $keepZeros = false): array
    {
        return collect($counts)
            ->filter(fn (int $count): bool => $keepZeros || $count > 0)
            ->sortDesc()
            ->map(fn (int $count, string $key): array => [$label($key), $this->n($count), $this->rate($count, $total)])
            ->values()
            ->all();
    }

    /**
     * Per phase: limits that ended in the period (verified cases only) and how many of them were exceeded — the
     * phase finished late, or is still open past its limit.
     *
     * @return list<list<string>>
     */
    private function deadlineRows(AnalyticsFilters $filters): array
    {
        $today = Carbon::parse(Carbon::now((string) config('courtwatch.display_timezone'))->toDateString())->toDateString();

        $rows = DB::table('case_deadlines as d')
            ->join('cases as c', 'c.id', '=', 'd.case_id')
            ->whereIn('c.status', WorkflowStatus::verifiedValues())
            ->whereNull('c.deleted_at')
            ->when($filters->courtId, fn ($query, int $id) => $query->where('c.court_id', $id))
            ->when($filters->caseType, fn ($query, CaseType $type) => $query->where('c.type', $type->value))
            ->when($filters->from, fn ($query, Carbon $from) => $query->where('d.due_on', '>=', $from->toDateString()))
            ->when($filters->until, fn ($query, Carbon $until) => $query->where('d.due_on', '<=', $until->toDateString()))
            ->where('d.due_on', '<', $today)
            ->whereIn('d.state', [DeadlineState::Open->value, DeadlineState::Met->value, DeadlineState::Late->value])
            ->selectRaw('d.rule as rule, count(*) as total')
            ->selectRaw("count(*) filter (where d.state = 'late' or d.state = 'open') as exceeded")
            ->groupBy('rule')
            ->get()
            ->keyBy('rule');

        return collect(DeadlineRule::cases())
            ->filter(fn (DeadlineRule $rule): bool => $rows->has($rule->value))
            ->map(fn (DeadlineRule $rule): array => [
                $rule->getLabel().' ('.__('deadlines.article', ['article' => $rule->article()]).')',
                $this->n((int) $rows[$rule->value]->total),
                $this->n((int) $rows[$rule->value]->exceeded),
                $this->rate((int) $rows[$rule->value]->exceeded, (int) $rows[$rule->value]->total),
            ])
            ->values()
            ->all();
    }
}
