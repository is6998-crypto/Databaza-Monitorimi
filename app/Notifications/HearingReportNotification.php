<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Models\HearingReport;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Hearing-report notification: in the staff panel (database) and by email. It never carries the text of the
 * report or of a comment (they may name people): only which report, what happened and who did it.
 */
class HearingReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SUBMITTED = 'submitted';

    public const RETURNED = 'returned';

    public const APPROVED = 'approved';

    public const COMMENTED = 'commented';

    public function __construct(
        public HearingReport $report,
        public string $event,
        public User $actor,
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
        $hearing = $this->report->hearing;

        return (string) __("reports.notifications.{$this->event}", [
            'date' => $hearing->hearing_date->format((string) config('courtwatch.date_format')),
            'case' => $hearing->case->display_label,
        ], $locale);
    }

    public function url(): string
    {
        return HearingReportResource::getUrl('view', ['record' => $this->report], panel: 'admin');
    }

    private function icon(): Heroicon
    {
        return match ($this->event) {
            self::SUBMITTED => ReportStatus::Submitted->getIcon(),
            self::RETURNED => ReportStatus::Returned->getIcon(),
            self::APPROVED => ReportStatus::Approved->getIcon(),
            default => Heroicon::OutlinedChatBubbleLeftRight,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        return FilamentNotification::make()
            ->title($this->title($locale))
            ->body((string) __('workflow.notifications.by', ['name' => $this->actor->name], $locale))
            ->icon($this->icon())
            ->iconColor($this->event === self::RETURNED ? 'warning' : 'info')
            ->actions([
                Action::make('open')->label((string) __('cases.actions.open', [], $locale))->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : null;

        return (new MailMessage)
            ->subject($this->title($locale))
            ->line($this->title($locale))
            ->line((string) __('workflow.notifications.by', ['name' => $this->actor->name], $locale))
            ->action((string) __('cases.actions.open', [], $locale), $this->url());
    }
}
