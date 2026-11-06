<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * The queue dashboard (/horizon) is for Super Admins who have set up 2FA, in every
 * environment (Horizon is open to everyone in "local" by default).
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        if (filled(config('backup.notifications.mail.to'))) {
            Horizon::routeMailNotificationsTo((string) config('backup.notifications.mail.to'));
        }
    }

    protected function authorization(): void
    {
        $this->gate();

        // Checked directly: Gate::before() lets Super Admins pass every gate, which would skip
        // the 2FA condition.
        Horizon::auth(fn ($request): bool => self::allowed($request->user()));
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null): bool => self::allowed($user));
    }

    public static function allowed(mixed $user): bool
    {
        return $user instanceof User
            && $user->is_active
            && $user->isSuperAdmin()
            && $user->hasMultiFactorAuthentication();
    }
}
