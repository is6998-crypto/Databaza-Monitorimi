<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\HearingReport;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Hearing reports. Monitors write and see their own; they may change one until it is approved. Editors see,
 * edit and comment on every report and decide when it is approved. Analysts read. Delete is admin-only and soft.
 */
class HearingReportPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'HearingReport';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var HearingReport $model */
        return $this->allowed($user, 'View')
            && (! $user->isMonitorOnly() || $model->isOwnedBy($user));
    }

    public function update(User $user, Model $model): bool
    {
        /** @var HearingReport $model */
        if (! $this->allowed($user, 'Update')) {
            return false;
        }

        if ($user->isMonitorOnly()) {
            return $model->isOwnedBy($user) && $model->status->isEditableByAuthor();
        }

        return true;
    }

    /**
     * Send to the editor: a draft or a returned report, by whoever may edit it.
     */
    public function submit(User $user, HearingReport $report): bool
    {
        return in_array($report->status, [ReportStatus::Draft, ReportStatus::Returned], true)
            && $this->update($user, $report);
    }

    /**
     * Approve a submitted report.
     */
    public function approve(User $user, HearingReport $report): bool
    {
        return $user->checkPermissionTo(Permissions::REVIEW) && $report->status === ReportStatus::Submitted;
    }

    /**
     * Send back to the monitor with a comment (also after approval, if something still has to change).
     */
    public function sendBack(User $user, HearingReport $report): bool
    {
        return $user->checkPermissionTo(Permissions::REVIEW)
            && in_array($report->status, [ReportStatus::Submitted, ReportStatus::Approved], true);
    }

    /**
     * Comments are a conversation between the editors and the monitor who wrote the report.
     */
    public function comment(User $user, HearingReport $report): bool
    {
        if (! $this->view($user, $report)) {
            return false;
        }

        return $user->checkPermissionTo(Permissions::REVIEW) || $report->isOwnedBy($user);
    }
}
