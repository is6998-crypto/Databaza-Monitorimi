<?php

namespace App\Notifications;

use App\Enums\ResearcherStatus;
use App\Models\ResearcherProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a researcher the outcome of the review of their account.
 */
class ResearcherReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ResearcherProfile $profile) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->profile->status === ResearcherStatus::Approved;
        $locale = $this->profile->user->preferredLocale();

        $mail = (new MailMessage)
            ->subject((string) __($approved ? 'research.notifications.approved' : 'research.notifications.not_approved', [], $locale))
            ->line((string) __($approved ? 'research.notifications.approved_body' : 'research.notifications.not_approved_body', [], $locale));

        if (filled($this->profile->review_note)) {
            $mail->line((string) $this->profile->review_note);
        }

        return $mail->action((string) __('research.notifications.open', [], $locale), url('/research'));
    }
}
