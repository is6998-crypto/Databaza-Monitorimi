<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\HearingReport;
use App\Models\HearingReportComment;
use App\Models\User;
use App\Notifications\HearingReportNotification;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * The life of a hearing report: the monitor sends it to the editor, the editor approves it or sends it back with
 * a comment, and both can comment. Every step is authorised here (again), audited and notified.
 */
class HearingReportService
{
    public function submit(HearingReport $report, User $user): void
    {
        Gate::forUser($user)->authorize('submit', $report);

        if (blank($report->plainText())) {
            throw ValidationException::withMessages(['body' => __('reports.errors.empty')]);
        }

        $this->setStatus($report, ReportStatus::Submitted, $user, ['submitted_at' => now()]);

        Notification::send($this->reviewers($user), new HearingReportNotification($report, HearingReportNotification::SUBMITTED, $user));
    }

    public function approve(HearingReport $report, User $user): void
    {
        Gate::forUser($user)->authorize('approve', $report);

        $this->setStatus($report, ReportStatus::Approved, $user, ['reviewed_at' => now(), 'reviewed_by' => $user->id]);

        $this->notifyAuthor($report, HearingReportNotification::APPROVED, $user);
    }

    public function sendBack(HearingReport $report, User $user, string $comment): void
    {
        Gate::forUser($user)->authorize('sendBack', $report);

        if (blank($comment)) {
            throw ValidationException::withMessages(['comment' => __('reports.errors.comment_required')]);
        }

        DB::transaction(function () use ($report, $user, $comment): void {
            $this->setStatus($report, ReportStatus::Returned, $user, ['reviewed_at' => now(), 'reviewed_by' => $user->id]);
            $this->storeComment($report, $user, $comment);
        });

        $this->notifyAuthor($report, HearingReportNotification::RETURNED, $user);
    }

    public function comment(HearingReport $report, User $user, string $body): HearingReportComment
    {
        Gate::forUser($user)->authorize('comment', $report);

        if (blank($body)) {
            throw ValidationException::withMessages(['body' => __('reports.errors.comment_required')]);
        }

        $comment = $this->storeComment($report, $user, $body);

        // The other side of the conversation: the author hears from an editor, the editors hear from the author.
        if ($report->isOwnedBy($user)) {
            $editors = $this->participants($report, $user);

            Notification::send($editors->isNotEmpty() ? $editors : $this->reviewers($user), new HearingReportNotification($report, HearingReportNotification::COMMENTED, $user));
        } else {
            $this->notifyAuthor($report, HearingReportNotification::COMMENTED, $user);
        }

        return $comment;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function setStatus(HearingReport $report, ReportStatus $status, User $user, array $attributes): void
    {
        $from = $report->status;

        $report->forceFill(['status' => $status, ...$attributes])->save();

        activity('reports')
            ->performedOn($report)
            ->causedBy($user)
            ->event('transition')
            ->withProperties(['from' => $from->value, 'to' => $status->value])
            ->log("{$from->value} → {$status->value}");
    }

    private function storeComment(HearingReport $report, User $user, string $body): HearingReportComment
    {
        $comment = $report->comments()->create(['user_id' => $user->id, 'body' => trim($body)]);

        // The log says that a comment was written, not what it says.
        activity('reports')
            ->performedOn($report)
            ->causedBy($user)
            ->event('commented')
            ->log('Comment added to the report');

        return $comment;
    }

    private function notifyAuthor(HearingReport $report, string $event, User $actor): void
    {
        $author = $report->author;

        if ($author !== null && $author->id !== $actor->id && $author->is_active) {
            $author->notify(new HearingReportNotification($report, $event, $actor));
        }
    }

    /**
     * Everyone who reviews (editors and super administrators), except the person acting.
     *
     * @return Collection<int, User>
     */
    private function reviewers(User $except): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($except->id)
            ->get()
            ->filter(fn (User $user): bool => $user->checkPermissionTo(Permissions::REVIEW))
            ->values();
    }

    /**
     * Reviewers who already took part in this report (reviewed it or commented on it).
     *
     * @return Collection<int, User>
     */
    private function participants(HearingReport $report, User $except): Collection
    {
        $ids = $report->comments()->pluck('user_id')->push($report->reviewed_by)->filter()->unique();

        return $this->reviewers($except)->whereIn('id', $ids->all())->values();
    }
}
