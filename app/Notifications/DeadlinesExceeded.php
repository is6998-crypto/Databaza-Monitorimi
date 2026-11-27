<?php

namespace App\Notifications;

use App\Models\CaseDeadline;
use App\Services\Deadlines\DeadlineNotifier;

/**
 * "A legal time limit was exceeded."
 */
class DeadlinesExceeded extends DeadlineNotification
{
    protected function titleOne(): string
    {
        return 'deadlines.notifications.one';
    }

    protected function titleMany(): string
    {
        return 'deadlines.notifications.many';
    }

    protected function line(CaseDeadline $deadline, ?string $locale): string
    {
        $extended = ($this->items[$deadline->id] ?? null) === DeadlineNotifier::TIER_EXTENDED && $deadline->extended_due_on !== null;
        $days = $extended
            ? (int) $deadline->extended_due_on->diffInDays(CaseDeadline::today(), true)
            : $deadline->daysOver();

        return (string) __($extended ? 'deadlines.notifications.line_extended' : 'deadlines.notifications.line', [
            'case' => $deadline->case->display_label,
            'days' => trans_choice('deadlines.units.days', $days, ['count' => $days], $locale),
            'limit' => $deadline->limitLabel($locale),
            'article' => $deadline->rule->article(),
            'since' => $deadline->started_on->format((string) config('courtwatch.date_format')),
        ], $locale);
    }
}
