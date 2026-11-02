<?php

namespace App\Policies;

use App\Enums\WorkflowStatus;
use App\Models\Hearing;
use App\Models\User;
use App\Support\CaseAccess;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class HearingPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Hearing';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var Hearing $model */
        if (! $this->allowed($user, 'View')) {
            return false;
        }

        return ! $user->isMonitorOnly()
            || CaseAccess::ownsHearing($user, $model)
            || CaseAccess::canSeeCase($user, $model->case);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Hearing $model */
        if (! $this->allowed($user, 'Update') || $model->status === WorkflowStatus::Published) {
            return false;
        }

        if ($user->isMonitorOnly()) {
            return CaseAccess::ownsHearing($user, $model) && $model->status->isEditableByAuthor();
        }

        return true;
    }

    public function submit(User $user, Hearing $hearing): bool
    {
        return in_array($hearing->status, [WorkflowStatus::Draft, WorkflowStatus::Returned], true)
            && $this->update($user, $hearing);
    }

    public function review(User $user, Hearing $hearing): bool
    {
        return $user->checkPermissionTo(Permissions::REVIEW)
            && in_array($hearing->status, [WorkflowStatus::Submitted, WorkflowStatus::InReview, WorkflowStatus::Approved], true);
    }

    public function publish(User $user, Hearing $hearing): bool
    {
        return $user->checkPermissionTo(Permissions::PUBLISH) && $hearing->status === WorkflowStatus::Approved;
    }

    public function unpublish(User $user, Hearing $hearing): bool
    {
        return $user->checkPermissionTo(Permissions::PUBLISH) && $hearing->status === WorkflowStatus::Published;
    }

    public function export(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::EXPORT_DATA);
    }
}
