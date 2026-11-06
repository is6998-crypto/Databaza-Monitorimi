<?php

namespace Migration\Import;

use App\Enums\StageType;
use App\Enums\WorkflowStatus;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Migration\Support\Text;
use Migration\Support\ValueMapper;
use RuntimeException;
use Throwable;

/**
 * Reads the legacy database (read only) and imports every configured module.
 *
 * Each case is imported in its own transaction: a failure is recorded in
 * `legacy_review_items` and the import continues. With $dryRun everything runs in one
 * transaction that is rolled back at the end, so only the report remains.
 */
final class LegacyImporter
{
    private readonly ReviewLog $review;

    private readonly IdMap $ids;

    private readonly ReferenceResolver $refs;

    private readonly ValueMapper $values;

    /**
     * @param  array<string, ModuleConfig>  $modules
     * @param  (Closure(string): void)|null  $progress
     */
    public function __construct(
        private readonly string $connection,
        private readonly array $modules,
        private readonly bool $dryRun = false,
        private readonly ?int $limit = null,
        private readonly ?Closure $progress = null,
    ) {
        $this->review = new ReviewLog;
        $this->ids = new IdMap;
        $this->refs = new ReferenceResolver;
        $this->values = ValueMapper::fromConfig();
    }

    /**
     * Problems that prevent an import: missing tables or mapped columns, invalid stages.
     *
     * @return list<string>
     */
    public function preflight(): array
    {
        $problems = [];
        $schema = Schema::connection($this->connection);

        foreach ($this->modules as $module) {
            if (! $schema->hasTable($module->table)) {
                $problems[] = "{$module->key}: table [{$module->table}] does not exist in the legacy database.";

                continue;
            }

            $columns = array_map('strtolower', $schema->getColumnListing($module->table));
            $missing = array_values(array_filter($module->mappedColumns(), fn (string $column): bool => ! in_array(strtolower($column), $columns, true)));

            if ($missing !== []) {
                $problems[] = "{$module->key}: mapped column(s) missing in [{$module->table}]: ".implode(', ', $missing).'.';
            }

            foreach (array_keys($module->stages) as $stage) {
                if (StageType::tryFrom($stage) === null) {
                    $problems[] = "{$module->key}: unknown stage [{$stage}] in the mapping.";
                }
            }
        }

        if (WorkflowStatus::tryFrom((string) config('legacy.status')) === null) {
            $problems[] = 'legacy.status must be one of: '.implode(', ', array_map(fn (WorkflowStatus $status): string => $status->value, WorkflowStatus::cases())).'.';
        }

        return $problems;
    }

    public function run(): Reconciliation
    {
        $problems = $this->preflight();

        if ($problems !== []) {
            throw new RuntimeException("The legacy mapping does not match the legacy database:\n - ".implode("\n - ", $problems));
        }

        $legacy = $this->legacyConnection();
        $status = WorkflowStatus::from((string) config('legacy.status', 'approved'));
        $importer = new CaseImporter($this->refs, $this->ids, $this->values, $status, (string) config('legacy.charge_stage', 'indictment'));
        $reconciliation = new Reconciliation($this->dryRun, $this->limit);
        $startedAt = now();

        activity()->disableLogging();

        if ($this->dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($this->modules as $module) {
                $stats = $this->importModule($legacy, $module, $importer);
                $reconciliation->add($stats, $this->review->counts($module->key));
            }

            $reconciliation->finish($this->refs->createdCounts(), $startedAt);

            activity()->enableLogging();
            activity('legacy')
                ->event('imported')
                ->withProperties($reconciliation->toArray())
                ->log($this->dryRun ? 'Legacy import (dry run)' : 'Legacy import');
        } finally {
            activity()->enableLogging();

            if ($this->dryRun) {
                DB::rollBack();
            }
        }

        return $reconciliation;
    }

    private function importModule(Connection $legacy, ModuleConfig $module, CaseImporter $importer): ModuleStats
    {
        $stats = new ModuleStats($module);
        $stats->legacyRows = $legacy->table($module->table)->count();

        $groups = $this->groups($legacy, $module);
        $stats->groups = count($groups);

        if ($this->limit !== null && count($groups) > $this->limit) {
            $groups = array_slice($groups, 0, $this->limit);
            $stats->rowsNotRead = $stats->legacyRows - array_sum(array_map('count', $groups));
        }

        $this->report("{$module->label}: {$stats->legacyRows} legacy rows in ".count($groups).' case groups');
        $batchSize = max(1, (int) config('legacy.batch_size', 200));

        foreach (array_chunk($groups, $batchSize) as $batchNumber => $batch) {
            $rows = $this->fetch($legacy, $module, array_merge(...$batch));

            foreach ($batch as $group) {
                $groupRows = array_values(array_filter(array_map(fn (string $id): ?LegacyRow => $rows[$id] ?? null, $group)));
                usort($groupRows, fn (LegacyRow $a, LegacyRow $b): int => strcmp($a->sortKey(), $b->sortKey()));

                $this->importGroup($module, $groupRows, $importer, $stats);
            }

            $this->report(sprintf('  %s: %d / %d case groups', $module->key, min(($batchNumber + 1) * $batchSize, count($groups)), count($groups)));
        }

        return $stats;
    }

    /**
     * @param  list<LegacyRow>  $rows
     */
    private function importGroup(ModuleConfig $module, array $rows, CaseImporter $importer, ModuleStats $stats): void
    {
        if ($rows === []) {
            return;
        }

        $this->ids->checkpoint();
        $this->refs->checkpoint();
        $reviewSnapshot = $this->review->snapshot();
        $statsSnapshot = $stats->snapshot();

        try {
            DB::transaction(fn () => $importer->import($module, $rows, $stats));
        } catch (Throwable $exception) {
            $this->ids->rollback();
            $this->refs->rollback();
            $this->review->restore($reviewSnapshot);
            $stats->restore($statsSnapshot);
            $stats->rowsFailed += count($rows);
            $stats->groupsFailed++;

            // SQL error messages contain the bound values (which can include personal data):
            // only the exception type and SQLSTATE are stored in clear text and logged.
            $summary = class_basename($exception).($exception instanceof QueryException ? ' (SQLSTATE '.$exception->getCode().')' : '');
            $rowIds = array_map(fn (LegacyRow $row): string => $row->id(), $rows);

            $this->review->add(
                $module,
                $rows[0]->id(),
                '*',
                ReviewLog::GROUP_FAILED,
                $summary,
                ['rows' => $rowIds, 'error' => $exception->getMessage(), 'data' => array_map(fn (LegacyRow $row): array => $row->data, $rows)],
                false,
            );

            Log::warning("Legacy import: case in {$module->key} failed ({$summary}); legacy rows ".implode(', ', $rowIds).' sent to review.');
        }
    }

    /**
     * Case groups (lists of legacy row ids) of a module.
     *
     * @return list<list<string>>
     */
    private function groups(Connection $legacy, ModuleConfig $module): array
    {
        $grouper = new CaseGrouper;
        $numberFields = array_values(array_filter((array) config('legacy.group_by', ['court_case_number']), fn (mixed $field): bool => is_string($field) && $module->column($field) !== null));
        $courtColumn = $module->column('court');
        $columns = array_values(array_unique(array_filter([$module->idColumn, $courtColumn, ...array_map(fn (string $field): ?string => $module->column($field), $numberFields)])));
        $courtKeys = [];

        foreach ($legacy->table($module->table)->select($columns)->orderBy($module->idColumn)->cursor() as $record) {
            $data = (array) $record;
            $rawCourt = $courtColumn === null ? null : Text::clean($data[$courtColumn] ?? null);
            $courtCacheKey = (string) $rawCourt;

            if (! isset($courtKeys[$courtCacheKey])) {
                $match = $this->refs->court($rawCourt);
                $courtKeys[$courtCacheKey] = $match === null ? 'raw:'.Text::key($rawCourt) : 'court:'.$match[0];
            }

            $numbers = [];

            foreach ($numberFields as $field) {
                $column = (string) $module->column($field);
                $numbers[$field] = Text::clean($data[$column] ?? null);
            }

            $grouper->add((string) $data[$module->idColumn], $courtKeys[$courtCacheKey], $numbers);
        }

        return $grouper->groups();
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, LegacyRow>
     */
    private function fetch(Connection $legacy, ModuleConfig $module, array $ids): array
    {
        $rows = [];

        foreach (array_chunk($ids, 500) as $chunk) {
            foreach ($legacy->table($module->table)->whereIn($module->idColumn, $chunk)->get() as $record) {
                $data = (array) $record;
                $rows[(string) $data[$module->idColumn]] = new LegacyRow($module, $data, $this->values, $this->review);
            }
        }

        return $rows;
    }

    /**
     * The legacy connection, switched to read-only mode where the driver allows it,
     * so the import can never change the legacy database.
     */
    private function legacyConnection(): Connection
    {
        $legacy = DB::connection($this->connection);

        match ($legacy->getDriverName()) {
            'mysql', 'mariadb' => $legacy->statement('SET SESSION TRANSACTION READ ONLY'),
            'pgsql' => $legacy->statement('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY'),
            'sqlite' => $legacy->statement('PRAGMA query_only = ON'),
            default => $this->report('Warning: cannot switch the legacy connection to read-only mode; use a read-only database user.'),
        };

        return $legacy;
    }

    private function report(string $message): void
    {
        if ($this->progress !== null) {
            ($this->progress)($message);
        }
    }
}
