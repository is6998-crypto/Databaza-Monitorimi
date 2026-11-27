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
 * For editors: hearings of high-priority cases coming up soon that nobody attends yet.
 */
class UnstaffedHearings extends Notification implements ShouldQueue
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
        return trans_choice('calendar.notifications.unstaffed_title', count($this->ids), ['count' => count($this->ids)], $locale);
    }

    /**
     * @return list<string>
     */
    public function lines(?string $locale = null): array
    {
        return $this->hearings()
            ->map(fn (ScheduledHearing $scheduled): string => $scheduled->whenLabel().' · '.$scheduled->case->display_label)
            ->values()
            ->all();
    }

    public function url(): string
    {
        return ScheduledHearingResource::getUrl('list', ['tableFilters' => ['nobody' => ['isActive' => true], 'high_priority' => ['isActive' => true]]], panel: 'admin');
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
            ->icon(Heroicon::OutlinedHandRaised)
            ->iconColor('warning')
            ->actions([
                Action::make('open')->label((string) __('calendar.notifications.assign', [], $locale))->url($this->url())->markAsRead(),
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

        return $mail->action((string) __('calendar.notifications.assign', [], $locale), $this->url());
    }
}
