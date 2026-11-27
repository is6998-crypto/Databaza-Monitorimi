<?php

namespace App\Services\Monitoring;

use App\Notifications\ErrorAlert;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Tells the administrators by e-mail when the application fails (an error that is logged), so that nobody has to
 * watch the logs. Built in, without an outside service: no data leaves the server except the e-mail itself.
 *
 * The message carries what is needed to find the problem — the kind of error, where in the code, which page — but
 * no personal data: no request data, no SQL values, quoted text and e-mail addresses are blanked, the user is an
 * id. The same error is reported at most once an hour, and at most a set number of errors an hour in all.
 */
class ErrorAlerts
{
    public function report(Throwable $exception): void
    {
        $to = trim((string) config('courtwatch.error_alerts.email'));

        if ($to === '' || ! (bool) config('courtwatch.error_alerts.enabled')) {
            return;
        }

        try {
            $fingerprint = sha1($exception::class.'|'.$exception->getFile().'|'.$exception->getLine());
            $window = 'error-alerts:'.now()->format('YmdH');

            if (! Cache::add("error-alerts:seen:{$fingerprint}", true, now()->addHour())) {
                return;
            }

            Cache::add($window, 0, now()->addHours(2));

            if ((int) Cache::increment($window) > max(1, (int) config('courtwatch.error_alerts.per_hour', 20))) {
                return;
            }

            Notification::route('mail', $to)->notifyNow(new ErrorAlert($this->details($exception)));
        } catch (Throwable) {
            // Reporting an error must never cause another one; the log still has it.
        }
    }

    /**
     * @return array<string, string>
     */
    public function details(Throwable $exception): array
    {
        $request = app()->runningInConsole() ? null : request();
        $user = $request?->user();

        return array_filter([
            'error' => $exception::class,
            'message' => $this->message($exception),
            'where' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $exception->getFile()).':'.$exception->getLine(),
            'page' => $request !== null ? $request->method().' /'.ltrim($request->path(), '/') : 'console: '.implode(' ', array_slice((array) ($_SERVER['argv'] ?? []), 1, 2)),
            'user' => $user !== null ? '#'.$user->getAuthIdentifier() : null,
            'environment' => (string) config('app.env'),
            'time' => now((string) config('courtwatch.display_timezone'))->format((string) config('courtwatch.datetime_format')),
        ], fn (?string $value): bool => filled($value));
    }

    private function message(Throwable $exception): string
    {
        // The SQL with placeholders, never its values.
        if ($exception instanceof QueryException) {
            return 'SQLSTATE '.($exception->errorInfo[0] ?? $exception->getCode()).': '.mb_substr($exception->getSql(), 0, 300);
        }

        $message = (string) preg_replace('/"[^"]*"|\'[^\']*\'|“[^”]*”/u', '"…"', $exception->getMessage());
        $message = (string) preg_replace('/[^\s@]+@[^\s@]+\.[^\s@]+/u', '[e-mail]', $message);

        return mb_substr($message, 0, 300);
    }
}
