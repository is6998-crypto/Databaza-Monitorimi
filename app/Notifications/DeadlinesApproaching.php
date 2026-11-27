<?php

namespace App\Notifications;

use App\Models\CaseDeadline;

/**
 * "A legal time limit is running out": sent once per phase, when most of the limit is used
 * (config deadlines.warning), so that there is still time to follow the case.
 */
class DeadlinesApproaching extends DeadlineNotification
{
    protected function titleOne(): string
    {
        return 'deadlines.notifications.warning_one';
    }

    protected function titleMany(): string
    {
        return 'deadlines.notifications.warning_many';
    }

    protected function iconColor(): string
    {
        return 'primary';
    }

    protected function line(CaseDeadline $deadline, ?string $locale): string
    {
        $left = max(0, $deadline->daysLeft());

        return (string) __($left === 0 ? 'deadlines.notifications.warning_line_today' : 'deadlines.notifications.warning_line', [
            'case' => $deadline->case->display_label,
            'days' => trans_choice('deadlines.units.days', $left, ['count' => $left], $locale),
            'limit' => $deadline->limitLabel($locale),
            'article' => $deadline->rule->article(),
            'due' => $deadline->due_on->format((string) config('courtwatch.date_format')),
        ], $locale);
    }
}
