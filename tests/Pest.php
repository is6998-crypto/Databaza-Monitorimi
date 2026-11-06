<?php

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Models\Reference\Court;
use App\Models\Reference\Monitor;
use App\Models\ResearcherProfile;
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

/**
 * A monitor (with 2FA) scoped to the given courts.
 *
 * @param  list<int>  $courtIds
 */
function monitorUser(array $courtIds = []): User
{
    $user = userWithRole(Role::Monitor);
    $monitor = Monitor::query()->create(['user_id' => $user->id, 'code' => 'T-'.$user->id]);
    $monitor->courts()->sync($courtIds);

    return $user->fresh();
}

function basicCourt(int $index = 0): Court
{
    return Court::query()->where('level', 'basic')->orderBy('id')->skip($index)->firstOrFail();
}

/**
 * A researcher account with a profile in the given status; approved researchers have
 * accepted the current data-use agreement unless $agreement is false.
 */
function researcher(ResearcherStatus $status = ResearcherStatus::Approved, bool $agreement = true, array $attributes = []): User
{
    $user = userWithRole(Role::Researcher, mfa: false, attributes: $attributes);
    $accepted = $agreement && $status === ResearcherStatus::Approved;

    ResearcherProfile::query()->create([
        'user_id' => $user->id,
        'institution' => 'Test University',
        'position' => 'Researcher',
        'research_purpose' => 'Testing access to anonymized court-monitoring data.',
        'status' => $status,
        'reviewed_at' => $status === ResearcherStatus::Pending ? null : now(),
        'agreement_version' => $accepted ? config('courtwatch.data_use_agreement_version') : null,
        'agreement_accepted_at' => $accepted ? now() : null,
    ]);

    return $user->fresh();
}
