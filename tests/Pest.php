<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
| Feature tests run against PostgreSQL (database courtwatch_test) inside a
| transaction per test. Roles and permissions are seeded once per run
| (see Tests\TestCase::$seeder).
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A user with the given role. Staff users get a TOTP secret unless $mfa is false.
 */
function userWithRole(Role $role, bool $mfa = true, array $attributes = []): User
{
    $factory = User::factory();

    if ($mfa) {
        $factory = $factory->withMfa();
    }

    $user = $factory->create($attributes);
    $user->assignRole($role->value);

    return $user->fresh();
}

function actingAsRole(Role $role, bool $mfa = true): User
{
    $user = userWithRole($role, $mfa);
    test()->actingAs($user);

    return $user;
}
