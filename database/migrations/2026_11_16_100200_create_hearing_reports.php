<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hearing reports: the monitor's written account of one court session, which an editor reads, edits and
 * comments on. Title, text and comments are encrypted at rest (they may name people) and stay internal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hearing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hearing_id')->unique()->constrained('hearings')->cascadeOnDelete();
            // Encrypted (Laravel encrypted cast).
            $table->text('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hearing_report_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hearing_report_id')->constrained('hearing_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Encrypted (Laravel encrypted cast).
            $table->text('body');
            $table->timestamps();
        });

        $this->grantPermissions();
    }

    /**
     * An existing installation is not re-seeded, so the permissions of the new record type are added here,
     * following the default matrix (new databases get them from the RolesAndPermissionsSeeder).
     */
    private function grantPermissions(): void
    {
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = Permissions::for(Permissions::CRUD, ['HearingReport']);

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Permissions::matrix() as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            $granted = array_values(array_intersect($permissions, $names));

            if ($role !== null && $granted !== []) {
                $role->givePermissionTo($granted);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('hearing_report_comments');
        Schema::dropIfExists('hearing_reports');
    }
};
