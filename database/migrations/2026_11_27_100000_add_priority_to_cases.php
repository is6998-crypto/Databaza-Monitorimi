<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Case priority (high, normal, low) and why, set by editors. High-priority cases come first in the staff lists,
 * the review queue and the monitor app. Internal only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('priority', 8)->default('normal')->index()->after('assigned_monitor_id');
            $table->text('priority_note')->nullable()->after('priority');
        });

        // An existing installation is not re-seeded: create the permission and grant it as the default matrix says.
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::PRIORITIZE_CASES, 'web');

        foreach (Permissions::matrix() as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role !== null && in_array(Permissions::PRIORITIZE_CASES, $permissions, true)) {
                $role->givePermissionTo(Permissions::PRIORITIZE_CASES);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['priority', 'priority_note']);
        });
    }
};
