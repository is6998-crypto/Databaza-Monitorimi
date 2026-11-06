<?php

namespace App\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Filament's login (5 attempts per minute per IP address, rate-limited 2FA codes) plus a
 * per-account lock: after 10 failed attempts (wrong password or 2FA code) an account is
 * locked for 15 minutes, whatever the IP address. Both panels use it.
 */
class Login extends BaseLogin
{
    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        $key = self::accountKey($this->data['email'] ?? null);

        if (RateLimiter::tooManyAttempts($key, self::maxAttempts())) {
            $this->getRateLimitedNotification(
                new TooManyRequestsException(static::class, 'authenticate', (string) request()->ip(), RateLimiter::availableIn($key)),
            )?->send();

            return null;
        }

        try {
            $response = parent::authenticate();
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, self::lockMinutes() * 60);

            throw $exception;
        }

        if ($response !== null) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    public static function accountKey(mixed $email): string
    {
        return 'login-account:'.hash('sha256', Str::lower(trim(is_string($email) ? $email : '')));
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) config('courtwatch.security.login_max_attempts', 10));
    }

    public static function lockMinutes(): int
    {
        return max(1, (int) config('courtwatch.security.login_lock_minutes', 15));
    }
}
