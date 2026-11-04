<?php

namespace App\Notifications;

use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Review-workflow notification: in the staff panel (database) and by email (Mailpit in dev).
 * Contains no personal names of parties — only the case number and court.
 */
class WorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CourtCase|Hearing $record,
        public WorkflowStatus $status,
        public User $actor,
        public ?string $comment = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function title(?string $locale = null): string
    {
        $key = match ($this->status) {
            WorkflowStatus::Submitted => 'workflow.notifications.submitted',
            WorkflowStatus::Returned => 'workflow.notifications.returned',
            WorkflowStatus::Approved => 'workflow.notifications.approved',
            WorkflowStatus::Published => 'workflow.notifications.published',
            default => 'workflow.notifications.changed',
        };

        return (string) __($key, ['item' => $this->label($locale)], $locale);
    }

    public function label(?string $locale = null): string
    {
        $record = $this->record;

        if ($record instanceof Hearing) {
            return (string) __('workflow.items.hearing', ['date' => $record->hearing_date->format('d.m.Y'), 'case' => $record->case->display_label], $locale);
        }

        return (string) __('workflow.items.case', ['case' => $record->display_label], $locale);
    }

    public function url(): string
    {
        return $this->record instanceof Hearing
            ? HearingResource::getUrl('view', ['record' => $this->record], panel: 'admin')
            : CourtCaseResource::getUrl('view', ['record' => $this->record], panel: 'admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        return FilamentNotification::make()
            ->title($this->title($locale))
            ->body($this->comment)
            ->icon($this->status->getIcon())
            ->iconColor($this->status->getColor())
            ->actions([
                Action::make('open')->label((string) __('cases.actions.open', [], $locale))->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        $mail = (new MailMessage)
            ->subject($this->title($locale))
            ->line($this->title($locale))
            ->line((string) __('workflow.notifications.by', ['name' => $this->actor->name], $locale));

        if (filled($this->comment)) {
            $mail->line((string) __('workflow.comment', [], $locale).': '.$this->comment);
        }

        return $mail->action((string) __('cases.actions.open', [], $locale), $this->url());
    }
}
