<?php

namespace Migration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Migration\Import\ModuleConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * php artisan legacy:inspect --connection=legacy
 *
 * Shows the legacy tables and columns next to the mapping, to adjust
 * migration/config/legacy.php. Prints names and counts only, never values.
 */
#[AsCommand(name: 'legacy:inspect')]
class InspectLegacyCommand extends Command
{
    protected $signature = 'legacy:inspect {--connection=legacy : Database connection of the legacy database}';

    protected $description = 'List legacy tables and columns against the import mapping (no data values)';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $exception) {
            $this->error("Cannot connect to the legacy database [{$connection}]: ".class_basename($exception).'.');

            return self::FAILURE;
        }

        $schema = Schema::connection($connection);
        $modules = ModuleConfig::all();
        $configured = [];
        $ignored = array_map('strval', (array) config('legacy.ignore_columns', []));
        $ok = true;

        foreach ($modules as $module) {
            $configured[] = $module->table;
            $this->newLine();
            $this->info("{$module->key} → table [{$module->table}]");

            if (! $schema->hasTable($module->table)) {
                $this->error('  table not found');
                $ok = false;

                continue;
            }

            $columns = $schema->getColumnListing($module->table);
            $mapped = $module->mappedColumns();
            $missing = array_diff($mapped, $columns);
            $unmapped = array_diff($columns, $mapped, $ignored);

            $this->line('  rows: '.DB::connection($connection)->table($module->table)->count().', columns: '.count($columns));
            $this->line('  mapped columns found: '.count(array_intersect($mapped, $columns)).' of '.count($mapped));

            if ($missing !== []) {
                $ok = false;
                $this->error('  mapped but missing: '.implode(', ', $missing));
            }

            if ($unmapped !== []) {
                $this->warn('  not mapped (their values will go to review as "unmapped_columns"): '.implode(', ', $unmapped));
            }
        }

        $others = array_values(array_diff(array_map(fn (mixed $table): string => is_array($table) ? (string) ($table['name'] ?? '') : (string) $table, $schema->getTableListing()), $configured));

        if ($others !== []) {
            $this->newLine();
            $this->line('Other legacy tables (not imported): '.implode(', ', $others));
        }

        $this->newLine();
        $ok ? $this->info('The mapping matches the legacy database.') : $this->error('Fix migration/config/legacy.php before importing.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
