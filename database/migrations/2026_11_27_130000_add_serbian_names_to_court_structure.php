<?php

use App\Services\Reference\CourtStructure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The interface is now in Serbian too: give the courts, branches, chambers, departments and divisions their Serbian
 * names. The structure sync is idempotent and only adds or updates names.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courts')) {
            app(CourtStructure::class)->sync();
        }
    }

    public function down(): void
    {
        // Names stay: they are reference data, not structure.
    }
};
