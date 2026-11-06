<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Models\Activity;

it('audits successful logins and stores the last login time', function () {
    $user = userWithRole(Role::Editor);

    event(new Login('web', $user, false));

    expect(Activity::query()->where('log_name', 'auth')->where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue()
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('audits failed logins', function () {
    event(new Failed('web', null, ['email' => 'nobody@example.test', 'password' => 'x']));

    $entry = Activity::query()->where('event', 'login_failed')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->properties['email'])->toBe('nobody@example.test')
        ->and($entry->properties)->not->toHaveKey('password');
});

it('audits role and permission changes', function () {
    $admin = actingAsRole(Role::SuperAdmin);
    $user = User::factory()->create();

    $user->assignRole(Role::Monitor->value);
    $user->removeRole(Role::Monitor->value);

    expect(Activity::query()->where('log_name', 'permissions')->where('event', 'role_attached')->where('causer_id', $admin->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'permissions')->where('event', 'role_detached')->exists())->toBeTrue();
});

it('audits user changes without logging passwords or 2FA secrets', function () {
    actingAsRole(Role::SuperAdmin);
    $user = User::factory()->create(['name' => 'Arta Test']);

    $user->update(['name' => 'Arta Changed', 'password' => 'new-password-123']);

    $entry = Activity::query()->where('subject_type', $user->getMorphClass())->where('subject_id', $user->id)->where('event', 'updated')->latest('id')->first();

    expect($entry->properties['attributes'])->toHaveKey('name')
        ->and($entry->properties['attributes'])->not->toHaveKey('password')
        ->and(json_encode($entry->properties))->not->toContain('app_authentication_secret');
});
