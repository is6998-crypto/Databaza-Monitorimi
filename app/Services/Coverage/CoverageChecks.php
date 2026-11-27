<?php

namespace App\Services\Coverage;

use App\Enums\CasePriority;
use App\Enums\DeadlineState;
use App\Enums\WorkflowStatus;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\ScheduledHearing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The gaps in the monitoring, for the editors' coverage page. Each check is a query, so the page can count it and
 * list it; the numbers of days are in config/courtwatch.php → coverage.
 */
class CoverageChecks
{
    public const NOT_FOLLOWED = 'not_followed';

    public const MISSING_REPORTS = 'missing_reports';

    public const MISSED_HEARINGS = 'missed_hearings';

    public const STALE_DRAFTS = 'stale_drafts';

    public const MISSING_FACTS = 'missing_facts';

    public const NO_MONITOR = 'no_monitor';

    public const ALL = [
        self::NOT_FOLLOWED, self::NO_MONITOR, self::MISSED_HEARINGS,
        self::MISSING_REPORTS, self::MISSING_FACTS, self::STALE_DRAFTS,
    ];

    public static function days(string $key, int $default): int
    {
        return max(1, (int) config("courtwatch.coverage.{$key}", $default));
    }

    private function today(): Carbon
    {
        return Carbon::parse(Carbon::now((string) config('courtwatch.display_timezone'))->toDateString());
    }

    /**
     * Active cases nobody has watched lately: no monitored hearing in the last N days (and the case is older than
     * that). Active means a legal time limit is still running (a criminal case mid-procedure) or the case is a
     * high priority for the team.
     *
     * @return Builder<CourtCase>
     */
    public function notFollowed(): Builder
    {
        $since = $this->today()->subDays(self::days('not_followed_days', 60));

        return CourtCase::query()
            ->where(fn (Builder $query) => $query
                ->where('cases.priority', CasePriority::High->value)
                ->orWhereHas('deadlines', fn (Builder $deadline) => $deadline->where('state', DeadlineState::Open->value)))
            ->where('cases.created_at', '<', $since)
            ->whereDoesntHave('hearings', fn (Builder $hearing) => $hearing->whereDate('hearing_date', '>=', $since->toDateString()))
            ->addSelect([
                'last_hearing_on' => Hearing::query()->selectRaw('max(hearing_date)')->whereColumn('hearings.case_id', 'cases.id')->whereNull('hearings.deleted_at'),
            ]);
    }

    /**
     * High-priority cases that have no monitor assigned.
     *
     * @return Builder<CourtCase>
     */
    public function noMonitor(): Builder
    {
        return CourtCase::query()
            ->where('cases.priority', CasePriority::High->value)
            ->whereNull('cases.assigned_monitor_id');
    }

    /**
     * Planned hearings of the last N days that passed without a monitored hearing.
     *
     * @return Builder<ScheduledHearing>
     */
    public function missedHearings(): Builder
    {
        return ScheduledHearing::query()
            ->missed()
            ->whereDate('scheduled_on', '>=', $this->today()->subDays(self::days('missed_hearings_days', 30))->toDateString());
    }

    /**
     * Recent hearings (the last N days) held more than a few days ago that still have no written report.
     *
     * @return Builder<Hearing>
     */
    public function missingReports(): Builder
    {
        return Hearing::query()
            ->whereDoesntHave('report')
            ->whereDate('hearing_date', '<=', $this->today()->subDays(self::days('report_after_days', 3))->toDateString())
            ->whereDate('hearing_date', '>=', $this->today()->subDays(self::days('report_window_days', 90))->toDateString());
    }

    /**
     * Criminal cases with a running time limit whose limit depends on an unanswered question (detention, single
     * judge or panel): until they are answered the longest limit is assumed.
     *
     * @return Builder<CourtCase>
     */
    public function missingFacts(): Builder
    {
        return CourtCase::query()
            ->whereHas('deadlines', fn (Builder $deadline) => $deadline->where('state', DeadlineState::Open->value))
            ->where(fn (Builder $query) => $query->whereNull('cases.in_detention')->orWhereNull('cases.trial_bench'));
    }

    /**
     * Drafts and returned records untouched for N days.
     *
     * @return array{cases: Builder<CourtCase>, hearings: Builder<Hearing>}
     */
    public function staleDrafts(): array
    {
        $before = now()->subDays(self::days('stale_draft_days', 14));
        $statuses = [WorkflowStatus::Draft->value, WorkflowStatus::Returned->value];

        return [
            'cases' => CourtCase::query()->whereIn('cases.status', $statuses)->where('cases.updated_at', '<', $before),
            'hearings' => Hearing::query()->whereIn('hearings.status', $statuses)->where('hearings.updated_at', '<', $before),
        ];
    }
}
