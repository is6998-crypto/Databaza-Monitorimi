<?php

namespace Migration\Import;

use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\LegacyIdMap;
use App\Models\LegacyReviewItem;
use App\Models\Measure;
use App\Models\Sentence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;

/**
 * The reconciliation report: for every legacy module, where each legacy row went
 * (imported, already imported, empty, failed, not read) and how many records the
 * new tables hold for it. Contains counts only, never values.
 */
final class Reconciliation
{
    /** @var array<string, array{stats: ModuleStats, review: array<string, int>, totals: array<string, int>}> */
    private array $modules = [];

    /** @var array<string, int> */
    private array $referencesCreated = [];

    private ?CarbonInterface $startedAt = null;

    private ?CarbonInterface $finishedAt = null;

    public function __construct(public readonly bool $dryRun = false, public readonly ?int $limit = null) {}

    /**
     * @param  array<string, int>  $review  review items added this run, by reason
     */
    public function add(ModuleStats $stats, array $review): void
    {
        $this->modules[$stats->module->key] = ['stats' => $stats, 'review' => $review, 'totals' => $this->totals($stats->module)];
    }

    /**
     * @param  array<string, int>  $referencesCreated
     */
    public function finish(array $referencesCreated, CarbonInterface $startedAt): void
    {
        $this->referencesCreated = $referencesCreated;
        $this->startedAt = $startedAt;
        $this->finishedAt = now();
    }

    public function balanced(): bool
    {
        foreach ($this->modules as $module) {
            if (! $module['stats']->balanced()) {
                return false;
            }
        }

        return true;
    }

    public function failedRows(): int
    {
        return array_sum(array_map(fn (array $module): int => $module['stats']->rowsFailed, $this->modules));
    }

    /**
     * @return list<string>
     */
    public static function tableHeaders(): array
    {
        return ['Module', 'Legacy rows', 'Imported', 'Already', 'Empty', 'Failed', 'Not read', 'Check', 'Cases', 'Parties', 'Hearings', 'Stages', 'Open review items'];
    }

    /**
     * @return list<list<string|int>>
     */
    public function tableRows(): array
    {
        $rows = [];

        foreach ($this->modules as $module) {
            $stats = $module['stats'];
            $totals = $module['totals'];

            $rows[] = [
                $stats->module->label,
                $stats->legacyRows,
                $stats->rowsImported,
                $stats->rowsAlreadyImported,
                $stats->rowsEmpty,
                $stats->rowsFailed,
                $stats->rowsNotRead,
                $stats->balanced() ? 'OK' : 'MISMATCH',
                $totals['cases'],
                $totals['parties'],
                $totals['hearings'],
                $totals['stages'],
                $totals['open_review_items'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $modules = [];

        foreach ($this->modules as $key => $module) {
            $stats = $module['stats'];

            $modules[$key] = [
                'label' => $stats->module->label,
                'legacy_table' => $stats->module->table,
                'legacy_rows' => $stats->legacyRows,
                'cases_in_legacy' => $stats->groups,
                'rows' => [
                    'imported' => $stats->rowsImported,
                    'already_imported' => $stats->rowsAlreadyImported,
                    'empty' => $stats->rowsEmpty,
                    'failed' => $stats->rowsFailed,
                    'not_read' => $stats->rowsNotRead,
                ],
                'balanced' => $stats->balanced(),
                'created_this_run' => $stats->created,
                'review_items_this_run' => $module['review'],
                'new_tables' => $module['totals'],
            ];
        }

        return [
            'dry_run' => $this->dryRun,
            'limit' => $this->limit,
            'started_at' => $this->startedAt?->toIso8601String(),
            'finished_at' => $this->finishedAt?->toIso8601String(),
            'balanced' => $this->balanced(),
            'references_created' => $this->referencesCreated,
            'modules' => $modules,
        ];
    }

    public function toMarkdown(): string
    {
        $lines = [
            '# Legacy import — reconciliation report',
            '',
            '- Run: '.($this->startedAt?->setTimezone((string) config('courtwatch.display_timezone'))->format('d.m.Y H:i:s') ?? '—').($this->dryRun ? ' (**dry run**: nothing was saved)' : ''),
            '- Result: '.($this->balanced() ? 'every legacy row is accounted for' : '**MISMATCH**: some legacy rows are not accounted for'),
            $this->limit !== null ? "- Limited to the first {$this->limit} cases per module" : '- All cases',
            '',
            '## Legacy rows → new tables',
            '',
            '| '.implode(' | ', self::tableHeaders()).' |',
            '|'.str_repeat(' --- |', count(self::tableHeaders())),
        ];

        foreach ($this->tableRows() as $row) {
            $lines[] = '| '.implode(' | ', array_map('strval', $row)).' |';
        }

        $lines[] = '';
        $lines[] = 'Legacy rows = imported + already imported (earlier runs) + empty + failed + not read (--limit).';
        $lines[] = '';
        $lines[] = '## Created in this run';
        $lines[] = '';

        foreach ($this->modules as $module) {
            $created = $module['stats']->created;
            ksort($created);
            $lines[] = '- **'.$module['stats']->module->label.'**: '.($created === [] ? 'nothing' : implode(', ', array_map(fn (string $type, int $count): string => "{$count} {$type}", array_keys($created), $created)));
        }

        $lines[] = '';
        $lines[] = '## Review items added in this run (table `legacy_review_items`)';
        $lines[] = '';

        foreach ($this->modules as $module) {
            $review = $module['review'];
            ksort($review);
            $lines[] = '- **'.$module['stats']->module->label.'**: '.($review === [] ? 'none' : implode(', ', array_map(fn (string $reason, int $count): string => "{$count} {$reason}", array_keys($review), $review)));
        }

        $lines[] = '';
        $lines[] = '## Reference records created (inactive, to check and merge)';
        $lines[] = '';
        $lines[] = $this->referencesCreated === []
            ? '- none'
            : implode("\n", array_map(fn (string $type, int $count): string => "- {$type}: {$count}", array_keys($this->referencesCreated), $this->referencesCreated));
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Writes the report as Markdown and JSON; returns the Markdown path.
     */
    public function write(string $directory): string
    {
        File::ensureDirectoryExists($directory, 0700);
        $base = rtrim($directory, '/').'/reconciliation-'.now()->format('Ymd-His').($this->dryRun ? '-dry-run' : '');

        File::put($base.'.md', $this->toMarkdown());
        File::put($base.'.json', (string) json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $base.'.md';
    }

    /**
     * Records in the new tables that came from this module (all runs).
     *
     * @return array<string, int>
     */
    private function totals(ModuleConfig $module): array
    {
        $cases = fn () => CourtCase::withTrashed()->where('legacy_module', $module->key)->select('id');

        return [
            'cases' => CourtCase::withTrashed()->where('legacy_module', $module->key)->count(),
            'parties' => CaseParty::withTrashed()->whereIn('case_id', $cases())->count(),
            'charges' => Charge::query()->whereIn('case_id', $cases())->count(),
            'stages' => CaseStage::query()->whereIn('case_id', $cases())->count(),
            'hearings' => Hearing::withTrashed()->whereIn('case_id', $cases())->count(),
            'measures' => Measure::query()->whereIn('case_id', $cases())->count(),
            'sentences' => Sentence::query()->whereIn('case_id', $cases())->count(),
            'mapped_rows' => LegacyIdMap::query()->where('legacy_table', $module->table)->where('target_type', 'case')->count(),
            'open_review_items' => LegacyReviewItem::query()->where('legacy_module', $module->key)->whereNull('resolved_at')->count(),
        ];
    }
}
