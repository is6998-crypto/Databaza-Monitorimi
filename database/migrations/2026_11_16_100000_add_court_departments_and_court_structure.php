<?php

use App\Models\Reference\Court;
use App\Services\Reference\CourtStructure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which departments exist in which court and branch (only Prishtina has the Special Department; branches have
 * the General Department only), and the full court structure in an existing database: the Commercial Court and
 * the Administrative Court, the chambers of the Supreme Court and the Court of Appeals, and the missing branches.
 * New databases get the same from the ReferenceDataSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->unique(['court_id', 'department_id']);
        });

        Schema::create('court_branch_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_branch_id')->constrained('court_branches')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->unique(['court_branch_id', 'department_id']);
        });

        // Only where reference data was already loaded; an empty database is filled by the seeder.
        if (Schema::hasTable('courts') && Court::query()->withTrashed()->exists()) {
            (new CourtStructure)->sync();
        }
    }

    public function down(): void
    {
        // The courts and branches stay: cases may already point at them.
        Schema::dropIfExists('court_branch_department');
        Schema::dropIfExists('court_department');
    }
};
