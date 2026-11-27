<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the people responsible for a case were warned that a legal time limit is about to run out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_deadlines', function (Blueprint $table): void {
            $table->timestamp('warned_at')->nullable()->after('due_on');
        });
    }

    public function down(): void
    {
        Schema::table('case_deadlines', function (Blueprint $table): void {
            $table->dropColumn('warned_at');
        });
    }
};
