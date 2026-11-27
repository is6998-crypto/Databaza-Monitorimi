<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "The platform ran into an error": the details App\Services\Monitoring\ErrorAlerts collected, without personal
 * data. In English and Albanian, since it goes to an address rather than to a user with a language.
 */
class ErrorAlert extends Notification
{
    /**
     * @param  array<string, string>  $details
     */
    public function __construct(public array $details) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('['.config('app.name').'] '.__('errors.alert.subject', ['error' => class_basename($this->details['error'] ?? 'Error')]))
            ->line(__('errors.alert.intro'));

        foreach ($this->details as $key => $value) {
            $mail->line('**'.__("errors.alert.fields.{$key}").':** '.$value);
        }

        return $mail->line(__('errors.alert.outro'));
    }
}
