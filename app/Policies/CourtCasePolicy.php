<?php

namespace App\Policies;

use App\Enums\WorkflowStatus;
use App\Models\CourtCase;
use App\Models\User;
use App\Support\CaseAccess;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class CourtCasePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'CourtCase';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var CourtCase $model */
        return $this->allowed($user, 'View') && CaseAccess::canSeeCase($user, $model);
    }

    /**
     * Monitors: own drafts and own records under review. Editors: any case that is not published.
     * Published records can only be changed by a Super Admin (Gate::before).
     */
    public function update(User $user, Model $model): bool
    {
        /** @var CourtCase $model */
        if (! $this->allowed($user, 'Update') || $model->status === WorkflowStatus::Published) {
            return false;
        }

        if ($user->isMonitorOnly()) {
            return CaseAccess::canSeeCase($user, $model)
                && CaseAccess::ownsCase($user, $model)
                && $model->status->isEditableByAuthor();
        }

        return true;
    }

    /**
     * Rows inside a case (parties, charges, stages, measures…) may be removed by whoever may
     * edit the case; monitors only while their case is still a draft or returned.
     */
    public function removeChildren(User $user, CourtCase $case): bool
    {
        if (! $this->update($user, $case)) {
            return false;
        }

        return ! $user->isMonitorOnly()
            || in_array($case->status, [WorkflowStatus::Draft, WorkflowStatus::Returned], true);
    }

    public function submit(User $user, CourtCase $case): bool
    {
        return in_array($case->status, [WorkflowStatus::Draft, WorkflowStatus::Returned], true)
            && $this->update($user, $case);
    }

    public function review(User $user, CourtCase $case): bool
    {
        return $user->checkPermissionTo(Permissions::REVIEW)
            && in_array($case->status, [WorkflowStatus::Submitted, WorkflowStatus::InReview, WorkflowStatus::Approved], true);
    }

    public function publish(User $user, CourtCase $case): bool
    {
        return $user->checkPermissionTo(Permissions::PUBLISH) && $case->status === WorkflowStatus::Approved;
    }

    public function unpublish(User $user, CourtCase $case): bool
    {
        return $user->checkPermissionTo(Permissions::PUBLISH) && $case->status === WorkflowStatus::Published;
    }

    public function assignMonitor(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::ASSIGN_MONITOR);
    }

    public function export(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::EXPORT_DATA);
    }
}
