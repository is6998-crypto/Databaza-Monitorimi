<?php

namespace Migration\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Migration\Import\LegacyImporter;
use Migration\Import\ModuleConfig;
use Migration\Import\Reconciliation;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * php artisan legacy:import --connection=legacy
 *
 * Idempotent and re-runnable: rows already imported are skipped, anything that cannot be
 * mapped goes to `legacy_review_items`, and a reconciliation report is written.
 * Run it only on the production server or a secure computer (docs/MIGRATION.md).
 */
#[AsCommand(name: 'legacy:import')]
class ImportLegacyCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'legacy:import
        {--connection=legacy : Database connection of the legacy database (config/database.php)}
        {--module=* : Import only these modules (see migration/config/legacy.php)}
        {--dry-run : Import everything inside a transaction, report, then roll back}
        {--limit= : Only the first N cases of each module (trial runs)}
        {--report= : Directory for the reconciliation report (default: storage/app/private/legacy)}
        {--force : Run in production without asking}';

    protected $description = 'Import the legacy MySQL database (idempotent, with a reconciliation report)';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');

        if (! is_array(config("database.connections.{$connection}"))) {
            $this->error("Database connection [{$connection}] is not configured (see LEGACY_DB_* in .env and docs/MIGRATION.md).");

            return self::FAILURE;
        }

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $exception) {
            $this->error("Cannot connect to the legacy database [{$connection}]: ".class_basename($exception).'.');

            return self::FAILURE;
        }

        try {
            /** @var list<string> $only */
            $only = array_values(array_filter(array_map('strval', (array) $this->option('module'))));
            $modules = ModuleConfig::all($only);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') === null ? null : max(1, (int) $this->option('limit'));

        if (! $dryRun && ! $this->confirmToProceed('This writes the legacy data into the production database.')) {
            return self::FAILURE;
        }

        $importer = new LegacyImporter($connection, $modules, $dryRun, $limit, fn (string $message) => $this->line($message));
        $problems = $importer->preflight();

        if ($problems !== []) {
            $this->error('The mapping in migration/config/legacy.php does not match the legacy database:');

            foreach ($problems as $problem) {
                $this->line(' - '.$problem);
            }

            $this->line('Run `php artisan legacy:inspect --connection='.$connection.'` to see the legacy tables and columns.');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run: nothing will be saved.' : 'Importing…');
        $reconciliation = $importer->run();

        $this->newLine();
        $this->table(Reconciliation::tableHeaders(), $reconciliation->tableRows());

        $directory = (string) ($this->option('report') ?: config('legacy.reports_path'));
        $this->info('Reconciliation report: '.$reconciliation->write($directory));

        if ($reconciliation->failedRows() > 0) {
            $this->warn("{$reconciliation->failedRows()} legacy row(s) could not be imported; see the review items with reason \"import_failed\".");
        }

        if (! $reconciliation->balanced()) {
            $this->error('Some legacy rows are not accounted for (see the report).');

            return self::FAILURE;
        }

        $this->info('Every legacy row is accounted for. Review the open items in the staff panel: Administration → Legacy review.');

        return self::SUCCESS;
    }
}
