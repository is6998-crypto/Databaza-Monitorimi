<?php

use App\Services\Reference\OffenceCatalogue;
use Illuminate\Database\Migrations\Migration;

/**
 * Loads the full list of criminal offences and their statistics categories (database/data) into an existing
 * database once; new databases get it from the ReferenceDataSeeder as well. Re-run later with
 * `php artisan reference:sync-offences`.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new OffenceCatalogue)->sync();
    }

    public function down(): void
    {
        // Reference data stays: charges may already point at the loaded offences.
    }
};
