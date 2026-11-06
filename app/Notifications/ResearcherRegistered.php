<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\Researchers\ResearcherProfileResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells administrators that a researcher is waiting for approval.
 */
class ResearcherRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $researcher) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title((string) __('research.notifications.registered', ['name' => $this->researcher->name]))
            ->actions([Action::make('review')->label((string) __('research.notifications.review'))->url(ResearcherProfileResource::getUrl(panel: 'admin'))->markAsRead()])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject((string) __('research.notifications.registered', ['name' => $this->researcher->name]))
            ->line((string) __('research.notifications.registered', ['name' => $this->researcher->name]))
            ->action((string) __('research.notifications.review'), ResearcherProfileResource::getUrl(panel: 'admin'));
    }
}
