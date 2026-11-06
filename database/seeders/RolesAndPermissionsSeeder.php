<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission and the five roles from proposal B4. Idempotent: safe to run
 * in production after deployments (`php artisan db:seed --class=RolesAndPermissionsSeeder`).
 * Existing role assignments made in the Shield role editor are only extended, never reduced.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach (Permissions::matrix() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->givePermissionTo($permissions);
        }

        // Make sure every role in the enum exists, even with no permissions.
        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
