<?php

namespace App\Console\Commands;

use App\Services\Monitoring\RestoreCheck;
use Illuminate\Console\Command;
use Throwable;

class CheckBackupRestore extends Command
{
    protected $signature = 'backup:restore-check
        {--disk= : Backup disk (default: the first of BACKUP_DISKS)}
        {--database= : Name of the temporary database (default: <database>_restore_check)}
        {--keep : Keep the temporary database for inspection}';

    protected $description = 'Restore the newest backup into a temporary database and check that it is complete and readable';

    public function handle(RestoreCheck $check): int
    {
        try {
            $result = $check->run($this->option('disk') ?: null, $this->option('database') ?: null, (bool) $this->option('keep'));
        } catch (Throwable $exception) {
            $this->error('Restore check failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup {$result['backup']} (taken {$result['taken_at']->toDateTimeString()}) restored into a temporary database.");
        $this->table(['Table', 'Rows in the backup', 'Rows now'], collect($result['tables'])->map(fn (array $counts, string $table): array => [
            $table,
            $counts['backup'] ?? 'missing',
            $counts['live'],
        ])->values()->all());

        if ($result['names_readable'] !== null) {
            $this->line($result['names_readable'] ? 'Encrypted names are readable with this APP_KEY.' : 'Encrypted names are NOT readable with this APP_KEY.');
        }

        foreach ($result['problems'] as $problem) {
            $this->error($problem);
        }

        if ($result['problems'] !== []) {
            return self::FAILURE;
        }

        $this->info('The backup can be restored.');

        return self::SUCCESS;
    }
}
