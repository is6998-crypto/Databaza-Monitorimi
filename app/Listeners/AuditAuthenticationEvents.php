<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Writes authentication events to the audit log (log name "auth").
 */
class AuditAuthenticationEvents
{
    public function login(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        activity('auth')
            ->causedBy($user instanceof User ? $user : null)
            ->event('login')
            ->withProperties($this->requestContext())
            ->log('Login');
    }

    public function failed(Failed $event): void
    {
        activity('auth')
            ->causedBy($event->user instanceof User ? $event->user : null)
            ->event('login_failed')
            ->withProperties([
                'email' => (string) ($event->credentials['email'] ?? ''),
                ...$this->requestContext(),
            ])
            ->log('Failed login');
    }

    public function logout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->event('logout')
            ->withProperties($this->requestContext())
            ->log('Logout');
    }

    public function passwordReset(PasswordReset $event): void
    {
        activity('auth')
            ->causedBy($event->user instanceof User ? $event->user : null)
            ->event('password_reset')
            ->withProperties($this->requestContext())
            ->log('Password reset');
    }

    /**
     * @return array<string, string|null>
     */
    private function requestContext(): array
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return [];
        }

        return [
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ];
    }
}
