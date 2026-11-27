<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Hearings tomorrow" for the monitor who attends them: case number, court, time and courtroom. No names and no
 * notes (free text may name people).
 */
class HearingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $ids  planned hearings
     */
    public function __construct(public array $ids) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return Collection<int, ScheduledHearing>
     */
    private function hearings(): Collection
    {
        return ScheduledHearing::query()->whereKey($this->ids)->whereHas('case')->with('case.court')->chronological()->get();
    }

    public function title(?string $locale = null): string
    {
        return trans_choice('calendar.notifications.reminder_title', count($this->ids), ['count' => count($this->ids)], $locale);
    }

    /**
     * @return list<string>
     */
    public function lines(?string $locale = null): array
    {
        return $this->hearings()->map(fn (ScheduledHearing $scheduled): string => collect([
            $scheduled->scheduled_time ?? __('calendar.no_time', [], $locale),
            $scheduled->case->display_label,
            $scheduled->courtroom ? __('calendar.fields.courtroom', [], $locale).' '.$scheduled->courtroom : null,
        ])->filter()->implode(' · '))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        return FilamentNotification::make()
            ->title($this->title($locale))
            ->body(implode('<br>', array_map(fn (string $line): string => e($line), $this->lines($locale))))
            ->icon(Heroicon::OutlinedCalendar)
            ->iconColor('info')
            ->actions([
                Action::make('open')->label((string) __('calendar.notifications.open', [], $locale))->url(ScheduledHearingResource::getUrl(panel: 'admin'))->markAsRead(),
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

        return $mail->action((string) __('calendar.notifications.open', [], $locale), ScheduledHearingResource::getUrl(panel: 'admin'));
    }
}
