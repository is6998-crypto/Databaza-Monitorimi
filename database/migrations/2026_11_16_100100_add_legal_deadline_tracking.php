<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legal time limits of the criminal procedure: the facts on a case that decide which limit applies, and one row
 * per case and phase with the day the limit ends (App\Services\Deadlines). Existing cases are filled in by
 * `php artisan deadlines:check`, which the scheduler runs every morning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Null = not recorded (treated as "at liberty", the longer limits).
            $table->boolean('in_detention')->nullable()->after('summary');
            // single_judge or panel; null = not recorded (treated as a panel, the longer limit).
            $table->string('trial_bench', 16)->nullable()->after('in_detention');
            $table->boolean('is_complex')->default(false)->after('trial_bench');
            $table->boolean('investigation_extended')->default(false)->after('is_complex');
        });

        Schema::create('case_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('rule', 32);
            $table->string('state', 16)->index();
            // Which limit of the rule applied: standard, detention, single_judge, panel, bench_unknown, extended.
            $table->string('basis', 16);
            $table->unsignedSmallInteger('limit_amount');
            $table->string('limit_unit', 8);
            $table->string('start_stage', 48);
            $table->date('started_on');
            $table->date('due_on')->index();
            // The longest limit the law allows (complex case, extended investigation), when it was not applied.
            $table->date('extended_due_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('extended_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['case_id', 'rule']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_deadlines');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['in_detention', 'trial_bench', 'is_complex', 'investigation_extended']);
        });
    }
};
