<?php

namespace App\Policies;

use App\Models\ScheduledHearing;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * The hearing calendar. Monitors see and plan the hearings of the cases in their courts and can say they will
 * attend one; editors plan every hearing and decide who attends; analysts read. Delete is admin-only and soft.
 */
class ScheduledHearingPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'ScheduledHearing';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var ScheduledHearing $model */
        return $this->allowed($user, 'View') && $model->canBeSeenBy($user);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var ScheduledHearing $model */
        return $this->allowed($user, 'Update') && $model->canBeSeenBy($user) && $model->hearing_id === null;
    }

    /**
     * Choose who attends (anyone): the people who assign monitors to cases.
     */
    public function assign(User $user, ScheduledHearing $scheduled): bool
    {
        return $user->checkPermissionTo(Permissions::ASSIGN_MONITOR) && $this->update($user, $scheduled);
    }

    /**
     * "I will attend": a monitor takes a hearing nobody attends yet.
     */
    public function attend(User $user, ScheduledHearing $scheduled): bool
    {
        return $user->monitor !== null
            && $scheduled->monitor_id === null
            && $scheduled->cancelled_at === null
            && ! $scheduled->scheduled_on->lt(ScheduledHearing::today())
            && $this->update($user, $scheduled);
    }

    public function cancel(User $user, ScheduledHearing $scheduled): bool
    {
        return $scheduled->cancelled_at === null && $this->update($user, $scheduled);
    }
}
