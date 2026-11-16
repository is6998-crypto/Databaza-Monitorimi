<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Models\CaseDeadline;
use App\Models\User;
use App\Services\Deadlines\DeadlineNotifier;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "A legal time limit was exceeded": in the staff panel (database) and by email. One notification names up to a
 * handful of phases and counts the rest. It holds no personal names: only the case number, the court, the phase
 * and the number of days.
 */
class DeadlinesExceeded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, string>  $items  deadline id => tier (DeadlineNotifier::TIER_*)
     */
    public function __construct(public array $items) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return Collection<int, CaseDeadline>
     */
    private function deadlines(): Collection
    {
        return CaseDeadline::query()
            ->whereKey(array_keys($this->items))
            ->whereHas('case')
            ->with('case.court')
            ->orderBy('due_on')
            ->get();
    }

    public function title(?string $locale = null): string
    {
        $deadlines = $this->deadlines();

        if ($deadlines->count() === 1) {
            /** @var CaseDeadline $deadline */
            $deadline = $deadlines->first();

            return (string) __('deadlines.notifications.one', ['phase' => $deadline->rule->getLabel()], $locale);
        }

        return trans_choice('deadlines.notifications.many', count($this->items), ['count' => count($this->items)], $locale);
    }

    /**
     * One line per phase: "P.nr. 12/24 · Gjykata… — Përpilimi i aktgjykimit: 5 ditë mbi afatin 30 ditë (neni 368 i KPP-së)".
     *
     * @return list<string>
     */
    public function lines(?string $locale = null): array
    {
        $deadlines = $this->deadlines();
        $shown = max(1, (int) config('deadlines.notification_lines', 5));
        $lines = [];

        foreach ($deadlines->take($shown) as $deadline) {
            $lines[] = $this->line($deadline, $deadlines->count() > 1, $locale);
        }

        if ($deadlines->count() > $shown) {
            $more = $deadlines->count() - $shown;
            $lines[] = trans_choice('deadlines.notifications.more', $more, ['count' => $more], $locale);
        }

        return $lines;
    }

    private function line(CaseDeadline $deadline, bool $withPhase, ?string $locale): string
    {
        $extended = ($this->items[$deadline->id] ?? null) === DeadlineNotifier::TIER_EXTENDED && $deadline->extended_due_on !== null;
        $days = $extended
            ? (int) $deadline->extended_due_on->diffInDays(CaseDeadline::today(), true)
            : $deadline->daysOver();

        $text = (string) __($extended ? 'deadlines.notifications.line_extended' : 'deadlines.notifications.line', [
            'case' => $deadline->case->display_label,
            'days' => trans_choice('deadlines.units.days', $days, ['count' => $days], $locale),
            'limit' => $deadline->limitLabel($locale),
            'article' => $deadline->rule->article(),
            'since' => $deadline->started_on->format((string) config('courtwatch.date_format')),
        ], $locale);

        return $withPhase ? $deadline->rule->getLabel().' — '.$text : $text;
    }

    public function url(): string
    {
        $deadlines = $this->deadlines();

        if ($deadlines->count() === 1) {
            /** @var CaseDeadline $deadline */
            $deadline = $deadlines->first();

            return CourtCaseResource::getUrl('view', ['record' => $deadline->case_id], panel: 'admin');
        }

        return CaseDeadlineResource::getUrl('index', panel: 'admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        return FilamentNotification::make()
            ->title($this->title($locale))
            // One phase per line; the panel shows the body as (sanitised) HTML.
            ->body(implode('<br>', array_map(fn (string $line): string => e($line), $this->lines($locale))))
            ->icon(Heroicon::OutlinedClock)
            ->iconColor('warning')
            ->actions([
                Action::make('open')->label((string) __('deadlines.notifications.open', [], $locale))->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        $mail = (new MailMessage)->subject($this->title($locale))->line($this->title($locale));

        foreach ($this->lines($locale) as $line) {
            $mail->line($line);
        }

        return $mail->action((string) __('deadlines.notifications.open', [], $locale), $this->url());
    }
}
