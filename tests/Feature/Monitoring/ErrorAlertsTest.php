<?php

use App\Notifications\ErrorAlert;
use App\Services\Monitoring\ErrorAlerts;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/*
| Error alerts: logged errors reach the administrators by e-mail, once an hour each, without personal data.
*/

beforeEach(function () {
    Notification::fake();
    Cache::flush();
    config(['courtwatch.error_alerts' => ['enabled' => true, 'email' => 'ops@example.test', 'per_hour' => 3]]);
});

it('e-mails the administrators when an error is logged, once an hour for the same error', function () {
    $error = new RuntimeException('Disk full');

    report($error);
    report($error);

    Notification::assertSentOnDemand(ErrorAlert::class, function (ErrorAlert $alert, array $channels, object $notifiable): bool {
        return $notifiable->routes['mail'] === 'ops@example.test'
            && $alert->details['error'] === RuntimeException::class
            && $alert->details['message'] === 'Disk full'
            && str_contains($alert->details['where'], 'tests/Feature/Monitoring/ErrorAlertsTest.php');
    });
    Notification::assertSentOnDemandTimes(ErrorAlert::class, 1);
});

it('stops after the hourly limit', function () {
    foreach (range(1, 5) as $n) {
        app(ErrorAlerts::class)->report(new RuntimeException("Error {$n}"));
    }

    // The same line of code: one alert. Different places in the code count separately, up to the limit.
    Notification::assertSentOnDemandTimes(ErrorAlert::class, 1);

    $errors = [new LogicException('a'), new InvalidArgumentException('b'), new DomainException('c'), new RangeException('d')];
    foreach ($errors as $error) {
        app(ErrorAlerts::class)->report($error);
    }

    Notification::assertSentOnDemandTimes(ErrorAlert::class, 3);
});

it('keeps personal data out of the message', function () {
    $alerts = app(ErrorAlerts::class);

    $query = new QueryException('pgsql', 'insert into case_parties (name) values (?)', ['Arben Testaj'], new PDOException('SQLSTATE[23505]: duplicate'));
    $quoted = new RuntimeException('Party "Arben Testaj" and \'Vjosa\' not found for arben@example.test');

    expect($alerts->details($query)['message'])->toContain('insert into case_parties (name) values (?)')->not->toContain('Testaj')
        ->and($alerts->details($quoted)['message'])->toBe('Party "…" and "…" not found for [e-mail]');
});

it('sends nothing when alerts are off or have no address', function (array $settings) {
    config(['courtwatch.error_alerts' => [...config('courtwatch.error_alerts'), ...$settings]]);

    report(new RuntimeException('Quiet'));

    Notification::assertNothingSent();
})->with([
    'switched off' => [['enabled' => false]],
    'no address' => [['email' => '']],
]);

it('does not alert about expected responses such as a missing page', function () {
    $this->get('/sq/cases/NOT-A-CASE')->assertNotFound();

    Notification::assertNothingSent();
});
