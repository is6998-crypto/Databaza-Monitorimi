<?php

use App\Enums\Role;
use Illuminate\Support\Facades\DB;

it('shows the staff login page', function () {
    $this->get('/admin/login')->assertOk()->assertSee('CourtWatch');
});

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forces staff without 2FA to set it up before using the panel', function (Role $role) {
    $this->actingAs(userWithRole($role, mfa: false));

    $this->get('/admin')->assertRedirectContains('multi-factor-authentication/set-up');
})->with([Role::SuperAdmin, Role::Editor, Role::Monitor, Role::Analyst]);

it('lets staff with 2FA into the panel', function (Role $role) {
    $this->actingAs(userWithRole($role));

    $this->get('/admin')->assertOk();
})->with([Role::SuperAdmin, Role::Editor, Role::Monitor, Role::Analyst]);

it('keeps researchers out of the staff panel', function () {
    $this->actingAs(userWithRole(Role::Researcher));

    $this->get('/admin')->assertForbidden();
});

it('keeps deactivated staff out of the panel', function () {
    $user = userWithRole(Role::Editor);
    $user->update(['is_active' => false]);

    $this->actingAs($user->fresh())->get('/admin')->assertForbidden();
});

it('renders the idle-timeout warning in the staff panel', function () {
    $this->actingAs(userWithRole(Role::Editor));

    $this->get('/admin')
        ->assertOk()
        ->assertSee('data-idle-timeout', false)
        ->assertSee('data-idle-extend', false);
});

it('uses a 30 minute idle timeout', function () {
    expect((int) config('session.lifetime'))->toBe(30);
})->skip(fn () => (int) env('SESSION_LIFETIME', 30) !== 30, 'SESSION_LIFETIME overridden');

it('extends the session from the warning modal', function () {
    $this->actingAs(userWithRole(Role::Monitor));

    $this->postJson('/session/ping')->assertOk()->assertJson(['ok' => true]);
});

it('rejects session pings from guests', function () {
    $this->postJson('/session/ping')->assertUnauthorized();
});

it('switches the interface language and remembers it on the user', function () {
    $user = userWithRole(Role::Analyst);

    $this->actingAs($user)->get('/locale/en')->assertRedirect();

    expect($user->fresh()->locale)->toBe('en')
        ->and(session('locale'))->toBe('en');
});

it('rejects unsupported languages', function () {
    $this->get('/locale/xx')->assertNotFound();
});

it('does not load avatars from third-party services', function () {
    $this->actingAs(userWithRole(Role::Editor));

    $this->get('/admin')->assertOk()->assertDontSee('ui-avatars.com');
});

it('does not expose the TOTP secret in serialised users', function () {
    $user = userWithRole(Role::Editor);

    expect($user->toArray())->not->toHaveKey('app_authentication_secret')
        ->and(DB::table('users')->where('id', $user->id)->value('app_authentication_secret'))->not->toBe('JBSWY3DPEHPK3PXP');
});
