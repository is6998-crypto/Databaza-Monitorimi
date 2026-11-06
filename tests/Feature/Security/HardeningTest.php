<?php

use App\Enums\Role;
use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/*
| Proposal B6.10: security headers, login rate limiting, no debug output, custom error
| pages, backups and a health check.
*/

it('sends security headers on every kind of response', function (string $url) {
    $response = $this->get($url);

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy')
        ->assertHeaderMissing('Strict-Transport-Security'); // plain HTTP

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'")
        ->not->toContain('https://');
})->with(['/sq', '/admin/login', '/research/login', '/docs/api', '/api/v1/cases', '/up', '/does-not-exist']);

it('adds HSTS and upgrades insecure requests over HTTPS', function () {
    $response = $this->get('https://localhost/sq');

    expect((string) $response->headers->get('Strict-Transport-Security'))->toContain('max-age=31536000')
        ->and((string) $response->headers->get('Content-Security-Policy'))->toContain('upgrade-insecure-requests');
});

it('can run the CSP in report-only mode or switch it off', function () {
    config(['courtwatch.security.csp' => 'report-only']);
    $this->get('/sq')->assertHeaderMissing('Content-Security-Policy')->assertHeader('Content-Security-Policy-Report-Only');

    config(['courtwatch.security.csp' => 'off']);
    $this->get('/sq')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('allows previews from the S3 storage origin only when files are on S3', function () {
    config([
        'courtwatch.attachments.disk' => 's3',
        'filesystems.disks.s3.endpoint' => 'https://files.example.org:9000',
    ]);

    $csp = (string) $this->get('/sq')->headers->get('Content-Security-Policy');
    expect($csp)->toContain('img-src \'self\' data: blob: https://files.example.org:9000')
        ->toContain('https://files.example.org:9000');
});

it('serves the API documentation without third-party assets', function () {
    $html = (string) $this->get('/docs/api')->assertOk()->getContent();

    foreach (['cdn.jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com', 'fonts.googleapis.com'] as $host) {
        expect($html)->not->toContain($host);
    }

    foreach (['lodash-4.17.10.min.js', 'highlight-11.6.0.min.js', 'highlight-obsidian-11.6.0.min.css', 'jets-0.14.1.min.js'] as $file) {
        expect($html)->toContain('/docs-assets/lib/'.$file)
            ->and(file_exists(public_path('docs-assets/lib/'.$file)))->toBeTrue();
    }

    expect($html)->toContain('/docs-assets/courtwatch.css')
        ->and(file_exists(public_path('docs-assets/courtwatch.css')))->toBeTrue();
});

it('shows a bilingual error page without technical details', function () {
    $this->get('/kjo-faqe-nuk-ekziston')
        ->assertNotFound()
        ->assertSee(__('errors.404.title', [], 'sq'))
        ->assertSee(__('errors.404.title', [], 'en'))
        ->assertDontSee('Symfony');

    config(['app.debug' => false]);
    Route::get('/_test/failure', fn () => throw new RuntimeException('database password is hunter2'));

    $this->get('/_test/failure')
        ->assertStatus(500)
        ->assertSee(__('errors.500.title', [], 'sq'))
        ->assertDontSee('hunter2')
        ->assertDontSee('RuntimeException');
});

it('answers API errors as JSON without a stack trace', function () {
    config(['app.debug' => false]);
    Route::get('/api/_test/failure', fn () => throw new RuntimeException('internal detail'));

    $response = $this->getJson('/api/_test/failure')->assertStatus(500);

    expect($response->json())->toBe(['message' => 'Server Error']);
});

it('keeps debug mode off in the production example configuration', function () {
    $env = (string) file_get_contents(base_path('.env.production.example'));

    expect($env)->toContain("APP_ENV=production\n")
        ->toContain("APP_DEBUG=false\n")
        ->toContain("SESSION_SECURE_COOKIE=true\n")
        ->toMatch('/^APP_KEY=$/m')
        ->toMatch('/^BACKUP_ARCHIVE_PASSWORD=$/m');
});

it('limits sign-in attempts per IP address', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $user = userWithRole(Role::Editor);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertNotified();

    $this->assertGuest();
});

it('locks an account after repeated failures from any address', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $user = userWithRole(Role::Editor, mfa: false);
    $key = Login::accountKey(strtoupper($user->email));

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(RateLimiter::attempts($key))->toBe(1);

    foreach (range(2, Login::maxAttempts()) as $attempt) {
        RateLimiter::hit($key, 900);
    }

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertNotified();

    $this->assertGuest();

    RateLimiter::clear($key);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
    expect(RateLimiter::attempts($key))->toBe(0);
});

it('reports the database and cache in the health check', function () {
    $this->get('/up')->assertOk();

    config(['cache.default' => 'missing-store']);

    $this->get('/up')->assertStatus(500);
});

it('opens the queue dashboard only to Super Admins with 2FA', function () {
    $this->get('/horizon')->assertForbidden();

    $this->actingAs(userWithRole(Role::Editor))->get('/horizon')->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin, mfa: false))->get('/horizon')->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin))->get('/horizon')->assertOk();
});

it('creates the first Super Admin from the command line', function () {
    $this->artisan('app:create-admin', ['email' => 'Drejtori@Example.org', '--name' => 'Drejtori Test'])
        ->expectsQuestion('Password (at least 12 characters, upper and lower case letters and numbers)', 'Fjalekalim-i-Forte-2026')
        ->expectsQuestion('Repeat the password', 'Fjalekalim-i-Forte-2026')
        ->assertSuccessful();

    $user = User::query()->where('email', 'drejtori@example.org')->firstOrFail();
    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->hasMultiFactorAuthentication())->toBeFalse()
        ->and(password_verify('Fjalekalim-i-Forte-2026', $user->password))->toBeTrue();

    $this->artisan('app:create-admin', ['email' => 'drejtori@example.org', '--name' => 'Again'])->assertFailed();

    $this->artisan('app:create-admin', ['email' => 'tjeter@example.org', '--name' => 'Weak'])
        ->expectsQuestion('Password (at least 12 characters, upper and lower case letters and numbers)', 'short')
        ->expectsQuestion('Repeat the password', 'short')
        ->assertFailed();

    expect(User::query()->where('email', 'tjeter@example.org')->exists())->toBeFalse();
});

it('backs up the database and private files, encrypted, every day', function () {
    expect(config('backup.backup.source.databases'))->toBe(['pgsql'])
        ->and(config('backup.backup.source.files.include'))->toBe([storage_path('app/private')])
        ->and(config('backup.backup.source.files.exclude'))->toContain(storage_path('app/private/legacy'))
        ->and(config('backup.backup.encryption'))->toBe('default')
        ->and(config('filesystems.disks.backups.driver'))->toBe('s3');

    $commands = collect(app(Schedule::class)->events())->map(fn (ScheduledEvent $event): string => (string) $event->command)->implode("\n");

    expect($commands)->toContain('backup:run')
        ->toContain('backup:clean')
        ->toContain('backup:monitor');
});

it('needs no bucket when files are stored locally', function () {
    config(['filesystems.default' => 'local', 'courtwatch.attachments.disk' => 'local']);

    $this->artisan('app:ensure-bucket')->expectsOutputToContain('no bucket needed')->assertSuccessful();
});

it('reports unreachable S3 storage instead of failing silently', function () {
    config([
        'courtwatch.attachments.disk' => 's3',
        'filesystems.disks.s3.endpoint' => 'http://127.0.0.1:9',
        'filesystems.disks.s3.bucket' => 'courtwatch',
        'filesystems.disks.s3.key' => 'test',
        'filesystems.disks.s3.secret' => 'test',
        'filesystems.disks.s3.region' => 'us-east-1',
        'filesystems.disks.s3.use_path_style_endpoint' => true,
    ]);

    $this->artisan('app:ensure-bucket', ['--retries' => 1])->expectsOutputToContain('Cannot reach the storage')->assertFailed();
});

it('boots with an empty backup notification address', function () {
    $config = require config_path('backup.php');

    expect($config['notifications']['mail']['to'])->toBeString()->not->toBe('');

    $_ENV['BACKUP_NOTIFICATION_EMAIL'] = '';
    putenv('BACKUP_NOTIFICATION_EMAIL=');

    try {
        $fallback = (require config_path('backup.php'))['notifications']['mail']['to'];
    } finally {
        unset($_ENV['BACKUP_NOTIFICATION_EMAIL']);
        putenv('BACKUP_NOTIFICATION_EMAIL');
    }

    expect(filter_var($fallback, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});

it('explains an incomplete S3 configuration', function () {
    config(['courtwatch.attachments.disk' => 's3', 'filesystems.disks.s3.bucket' => '', 'filesystems.disks.s3.region' => '']);

    $this->artisan('app:ensure-bucket', ['--retries' => 1])->expectsOutputToContain('not fully configured')->assertFailed();
});
