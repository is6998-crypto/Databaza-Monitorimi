<?php

namespace Migration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Migration\Fixtures\LegacyFixture;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * php artisan legacy:fixture
 *
 * Writes a FAKE legacy database (SQLite) for trying the importer without real data.
 */
#[AsCommand(name: 'legacy:fixture')]
class MakeLegacyFixtureCommand extends Command
{
    protected $signature = 'legacy:fixture
        {path? : SQLite file to create (default: storage/app/private/legacy/fixture.sqlite)}
        {--cases=20 : Generated cases per module, in addition to the hand-written scenarios}';

    protected $description = 'Create a fake legacy database (SQLite) to try legacy:import';

    public function handle(): int
    {
        $path = (string) ($this->argument('path') ?: storage_path('app/private/legacy/fixture.sqlite'));
        File::ensureDirectoryExists(dirname($path), 0700);
        File::put($path, '');

        config(['database.connections.legacy_fixture' => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => false]]);
        DB::purge('legacy_fixture');

        $expected = (new LegacyFixture('legacy_fixture'))->create(max(0, (int) $this->option('cases')));

        $this->table(['Module', 'Legacy rows', 'Cases', 'Hearings', 'Parties'], array_map(
            fn (string $module, array $counts): array => [$module, $counts['rows'], $counts['cases'], $counts['hearings'], $counts['parties']],
            array_keys($expected),
            $expected,
        ));

        $this->info("Fake legacy database written to {$path}. Try the importer with:");
        $this->line("  LEGACY_DB_DRIVER=sqlite LEGACY_DB_DATABASE={$path} php artisan legacy:import --connection=legacy --dry-run");

        return self::SUCCESS;
    }
}
