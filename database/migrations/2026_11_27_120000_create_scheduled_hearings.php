<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The hearing calendar: court sessions that are coming up, who will attend them and whether they were monitored.
 * A planned hearing comes from the "next hearing" date a monitor notes at a hearing, or is entered by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table): void {
            $table->date('next_hearing_on')->nullable()->after('notes');
            $table->string('next_hearing_time', 5)->nullable()->after('next_hearing_on');
        });

        Schema::create('scheduled_hearings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            // The day and time in Kosovo, as the court announces them.
            $table->date('scheduled_on')->index();
            $table->string('scheduled_time', 5)->nullable();
            $table->string('courtroom', 60)->nullable();
            $table->text('note')->nullable();
            // Who will attend.
            $table->foreignId('monitor_id')->nullable()->constrained('monitors')->nullOnDelete();
            // The hearing at which this date was announced, and the hearing that was monitored on the day.
            $table->foreignId('source_hearing_id')->nullable()->unique()->constrained('hearings')->nullOnDelete();
            $table->foreignId('hearing_id')->nullable()->constrained('hearings')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('unstaffed_notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'scheduled_on']);
        });

        $this->grantPermissions();
    }

    /**
     * An existing installation is not re-seeded: create the permissions of the new record type and grant them as
     * the default matrix says (new databases get them from the RolesAndPermissionsSeeder).
     */
    private function grantPermissions(): void
    {
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = Permissions::for(Permissions::CRUD, ['ScheduledHearing']);

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
        Schema::dropIfExists('scheduled_hearings');

        Schema::table('hearings', function (Blueprint $table): void {
            $table->dropColumn(['next_hearing_on', 'next_hearing_time']);
        });
    }
};
