<?php

namespace App\Services\Deadlines;

use App\Enums\DeadlineState;
use App\Enums\Role;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\User;
use App\Notifications\DeadlineNotification;
use App\Notifications\DeadlinesApproaching;
use App\Notifications\DeadlinesExceeded;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Tells the people responsible for a case about its legal time limits: the monitor of the case (and whoever
 * recorded it), every editor and every super administrator.
 *
 * - warn(): a phase has used most of its limit (config deadlines.warning). Once per phase.
 * - notify(): a phase has run past its limit. Once, and a second time only if the longest limit the law allows
 *   (complex case, extended investigation) passes too.
 *
 * One person gets one notification of each kind per run, however many phases it covers.
 */
class DeadlineNotifier
{
    public const TIER_WARNING = 'warning';

    public const TIER_LIMIT = 'limit';

    public const TIER_EXTENDED = 'extended';

    /**
     * Warns about the phases whose limit is about to run out.
     *
     * @return array{deadlines: int, recipients: int}
     */
    public function warn(): array
    {
        $today = CaseDeadline::today();
        $horizon = $today->copy()->addDays(max(1, (int) config('deadlines.warning.max_days_before', 30)))->toDateString();

        $deadlines = CaseDeadline::query()
            ->where('state', DeadlineState::Open->value)
            ->whereNull('warned_at')
            ->whereNull('notified_at')
            ->whereDate('due_on', '>=', $today->toDateString())
            ->whereDate('due_on', '<=', $horizon)
            ->whereHas('case')
            ->with(['case.assignedMonitor.user', 'case.creator'])
            ->orderBy('due_on')
            ->get()
            ->filter(fn (CaseDeadline $deadline): bool => $deadline->needsWarning());

        return $this->send(
            $deadlines,
            fn (): string => self::TIER_WARNING,
            fn (CaseDeadline $deadline) => $deadline->forceFill(['warned_at' => now()])->save(),
            fn (array $items): DeadlineNotification => new DeadlinesApproaching($items),
        );
    }

    /**
     * Reports the phases that ran past their limit.
     *
     * @return array{deadlines: int, recipients: int}
     */
    public function notify(): array
    {
        $today = CaseDeadline::today()->toDateString();

        $deadlines = CaseDeadline::query()
            ->where('state', DeadlineState::Open->value)
            ->whereHas('case')
            ->where(function ($query) use ($today): void {
                $query->where(fn ($query) => $query->whereNull('notified_at')->whereDate('due_on', '<', $today))
                    ->orWhere(fn ($query) => $query->whereNull('extended_notified_at')->whereNotNull('extended_due_on')->whereDate('extended_due_on', '<', $today));
            })
            ->with(['case.assignedMonitor.user', 'case.creator'])
            ->orderBy('due_on')
            ->get();

        $now = now();

        return $this->send(
            $deadlines,
            // When both limits passed before the first check, only the longer one is reported.
            fn (CaseDeadline $deadline): string => $deadline->isPastExtension() ? self::TIER_EXTENDED : self::TIER_LIMIT,
            fn (CaseDeadline $deadline, string $tier) => $deadline->forceFill([
                'notified_at' => $deadline->notified_at ?? $now,
                'extended_notified_at' => $tier === self::TIER_EXTENDED ? $now : $deadline->extended_notified_at,
            ])->save(),
            fn (array $items): DeadlineNotification => new DeadlinesExceeded($items),
        );
    }

    /**
     * @param  EloquentCollection<int, CaseDeadline>|Collection<int, CaseDeadline>  $deadlines
     * @param  callable(CaseDeadline): string  $tier
     * @param  callable(CaseDeadline, string): mixed  $mark
     * @param  callable(array<int, string>): DeadlineNotification  $notification
     * @return array{deadlines: int, recipients: int}
     */
    private function send(Collection $deadlines, callable $tier, callable $mark, callable $notification): array
    {
        if ($deadlines->isEmpty()) {
            return ['deadlines' => 0, 'recipients' => 0];
        }

        $supervisors = User::query()
            ->where('is_active', true)
            ->role([Role::Editor->value, Role::SuperAdmin->value])
            ->get()
            ->keyBy('id');

        /** @var array<int, array<int, string>> $perUser user id => [deadline id => tier] */
        $perUser = [];
        /** @var array<int, User> $users */
        $users = [];

        foreach ($deadlines as $deadline) {
            $deadlineTier = $tier($deadline);

            foreach ($this->recipients($deadline->case, $supervisors) as $user) {
                $users[$user->id] = $user;
                $perUser[$user->id][$deadline->id] = $deadlineTier;
            }

            $mark($deadline, $deadlineTier);
        }

        foreach ($perUser as $userId => $items) {
            $users[$userId]->notify($notification($items));
        }

        return ['deadlines' => $deadlines->count(), 'recipients' => count($perUser)];
    }

    /**
     * @param  Collection<int, User>  $supervisors
     * @return Collection<int, User>
     */
    private function recipients(CourtCase $case, Collection $supervisors): Collection
    {
        $own = collect([$case->assignedMonitor?->user, $case->creator])
            ->filter(fn (?User $user): bool => $user !== null
                && $user->is_active
                && $user->isStaff()
                && Gate::forUser($user)->allows('view', $case));

        return $supervisors->values()->concat($own)->unique('id')->values();
    }
}
