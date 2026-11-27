<?php

namespace App\Services\Calendar;

use App\Enums\CasePriority;
use App\Enums\Role;
use App\Models\ScheduledHearing;
use App\Models\User;
use App\Notifications\HearingReminder;
use App\Notifications\UnstaffedHearings;
use Illuminate\Support\Facades\Gate;

/**
 * Daily messages from the hearing calendar:
 *
 * - remind(): the monitor who attends a hearing tomorrow is reminded (once per hearing, again if it moves);
 * - warnUnstaffed(): editors and administrators hear about hearings of high-priority cases in the coming days that
 *   nobody attends yet (once per hearing, again if it moves).
 */
class CalendarNotifier
{
    /**
     * @return array{hearings: int, recipients: int}
     */
    public function remind(): array
    {
        $tomorrow = ScheduledHearing::today()->addDay()->toDateString();

        $hearings = ScheduledHearing::query()
            ->open()
            ->whereDate('scheduled_on', $tomorrow)
            ->whereNotNull('monitor_id')
            ->whereNull('reminded_at')
            ->whereHas('case')
            ->with(['monitor.user', 'case'])
            ->get();

        /** @var array<int, list<int>> $perUser */
        $perUser = [];
        /** @var array<int, User> $users */
        $users = [];

        foreach ($hearings as $scheduled) {
            $user = $scheduled->monitor?->user;

            if ($user !== null && $user->is_active && Gate::forUser($user)->allows('view', $scheduled)) {
                $users[$user->id] = $user;
                $perUser[$user->id][] = $scheduled->id;
            }

            $scheduled->forceFill(['reminded_at' => now()])->saveQuietly();
        }

        foreach ($perUser as $userId => $ids) {
            $users[$userId]->notify(new HearingReminder($ids));
        }

        return ['hearings' => $hearings->count(), 'recipients' => count($perUser)];
    }

    /**
     * @return array{hearings: int, recipients: int}
     */
    public function warnUnstaffed(): array
    {
        $today = ScheduledHearing::today();
        $until = $today->copy()->addDays(max(1, (int) config('courtwatch.calendar.unstaffed_warning_days', 7)))->toDateString();

        $hearings = ScheduledHearing::query()
            ->upcoming()
            ->whereDate('scheduled_on', '<=', $until)
            ->whereNull('monitor_id')
            ->whereNull('unstaffed_notified_at')
            ->whereHas('case', fn ($case) => $case->where('priority', CasePriority::High->value))
            ->get();

        if ($hearings->isEmpty()) {
            return ['hearings' => 0, 'recipients' => 0];
        }

        $recipients = User::query()->where('is_active', true)->role([Role::Editor->value, Role::SuperAdmin->value])->get();
        $ids = $hearings->modelKeys();

        foreach ($recipients as $user) {
            $user->notify(new UnstaffedHearings(array_values(array_map('intval', $ids))));
        }

        ScheduledHearing::query()->whereKey($ids)->update(['unstaffed_notified_at' => now()]);

        return ['hearings' => $hearings->count(), 'recipients' => $recipients->count()];
    }
}
