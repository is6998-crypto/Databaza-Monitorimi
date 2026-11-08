<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Owner's review (Nov 2026): the reason for a delay becomes multi-choice, hearings record
 * whether a plea agreement was reached, and accused get an economic condition next to the
 * employment status (the former "economic status" list).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->jsonb('delay_causes')->nullable()->after('delay_minutes');
            $table->boolean('plea_agreement')->nullable()->after('postponement_reasons');
        });

        DB::table('hearings')->whereNotNull('delay_cause')->update(['delay_causes' => DB::raw('jsonb_build_array(delay_cause)')]);

        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('delay_cause');
        });

        Schema::table('case_parties', function (Blueprint $table) {
            $table->string('economic_condition', 24)->nullable()->after('economic_status_id');
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->string('delay_cause', 16)->nullable()->after('delay_minutes');
        });

        DB::table('hearings')->whereNotNull('delay_causes')->update(['delay_cause' => DB::raw('delay_causes->>0')]);

        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn(['delay_causes', 'plea_agreement']);
        });

        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropColumn('economic_condition');
        });
    }
};
