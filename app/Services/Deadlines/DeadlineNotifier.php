<?php

namespace App\Services\Deadlines;

use App\Enums\DeadlineState;
use App\Enums\Role;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\User;
use App\Notifications\DeadlinesExceeded;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Tells the people responsible for a case when one of its phases has run past its legal time limit: the monitor
 * of the case (and whoever recorded it), every editor and every super administrator. Each exceeded limit is
 * reported once, and a second time only if the longest limit the law allows (complex case, extended
 * investigation) passes too. One person gets one notification per run, however many limits it covers.
 */
class DeadlineNotifier
{
    public const TIER_LIMIT = 'limit';

    public const TIER_EXTENDED = 'extended';

    /**
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
        $now = now();

        foreach ($deadlines as $deadline) {
            // When both limits passed before the first check, only the longer one is reported.
            $tier = $deadline->isPastExtension() ? self::TIER_EXTENDED : self::TIER_LIMIT;

            foreach ($this->recipients($deadline->case, $supervisors) as $user) {
                $users[$user->id] = $user;
                $perUser[$user->id][$deadline->id] = $tier;
            }

            $deadline->forceFill([
                'notified_at' => $deadline->notified_at ?? $now,
                'extended_notified_at' => $tier === self::TIER_EXTENDED ? $now : $deadline->extended_notified_at,
            ])->save();
        }

        foreach ($perUser as $userId => $items) {
            $users[$userId]->notify(new DeadlinesExceeded($items));
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
