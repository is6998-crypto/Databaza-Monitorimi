<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fuzzy search with pg_trgm (proposal B2/B6.6). PostgreSQL only.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $columns = [
        'cases' => ['court_case_number', 'indictment_number', 'police_case_number'],
        'judges' => ['name'],
        'prosecutors' => ['name'],
        'criminal_offences' => ['title_sq', 'title_en', 'article_number'],
        'case_parties' => ['name_tokens'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("CREATE INDEX IF NOT EXISTS {$table}_{$column}_trgm ON {$table} USING gin ({$column} gin_trgm_ops)");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("DROP INDEX IF EXISTS {$table}_{$column}_trgm");
            }
        }
    }
};
