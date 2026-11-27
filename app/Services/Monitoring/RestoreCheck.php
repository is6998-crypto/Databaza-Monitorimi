<?php

namespace App\Services\Monitoring;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Spatie\Backup\BackupDestination\BackupDestination;
use ZipArchive;

/**
 * Proves that the newest backup can be restored: it is downloaded, unpacked with the backup password, loaded into a
 * temporary database with psql, and checked — the main tables are there, there are users, and a party name in it
 * can be decrypted with this server's APP_KEY (a backup restored with another key is unreadable). The temporary
 * database and files are removed afterwards. Nothing in the live database is touched.
 */
class RestoreCheck
{
    /** Tables whose rows are counted in the restored copy. */
    public const TABLES = ['users', 'cases', 'case_parties', 'hearings', 'hearing_reports', 'scheduled_hearings', 'case_deadlines', 'activity_log'];

    private const ADMIN = 'restore_check_admin';

    private const RESTORED = 'restore_check';

    /**
     * @return array{backup: string, taken_at: CarbonInterface, tables: array<string, array{backup: int|null, live: int}>, names_readable: bool|null, problems: list<string>}
     */
    public function run(?string $disk = null, ?string $database = null, bool $keep = false): array
    {
        $disk ??= (string) (config('backup.backup.destination.disks')[0] ?? 'backups_local');
        $live = (array) config('database.connections.'.config('database.default'));
        $database ??= ((string) $live['database']).'_restore_check';

        if (preg_match('/^[a-z0-9_]+$/', $database) !== 1) {
            throw new RuntimeException("Invalid name for the temporary database: {$database}");
        }

        $backup = BackupDestination::create($disk, (string) config('backup.backup.name'))->newestBackup();

        if ($backup === null) {
            throw new RuntimeException("No backup found on the disk \"{$disk}\".");
        }

        $work = storage_path('app/backup-temp/restore-check-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($work);

        config(['database.connections.'.self::ADMIN => $live, 'database.connections.'.self::RESTORED => [...$live, 'database' => $database]]);

        try {
            $archive = "{$work}/backup.zip";
            $stream = $backup->stream();
            file_put_contents($archive, $stream);

            $dump = $this->extractDump($archive, $work);

            $this->dropDatabase($database);
            DB::connection(self::ADMIN)->statement("CREATE DATABASE \"{$database}\"");

            $result = Process::timeout(1800)
                ->env(['PGPASSWORD' => (string) ($live['password'] ?? '')])
                ->run(['psql', '--quiet', '--no-psqlrc', '-v', 'ON_ERROR_STOP=1', '-h', (string) $live['host'], '-p', (string) $live['port'], '-U', (string) $live['username'], '-d', $database, '-f', $dump]);

            if ($result->failed()) {
                throw new RuntimeException('psql could not load the backup: '.trim(mb_substr($result->errorOutput(), 0, 500)));
            }

            return $this->verify($backup->path(), $backup->date());
        } finally {
            DB::purge(self::RESTORED);

            if (! $keep) {
                $this->dropDatabase($database);
            }

            DB::purge(self::ADMIN);
            File::deleteDirectory($work);
        }
    }

    private function extractDump(string $archive, string $work): string
    {
        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException('The backup archive could not be opened.');
        }

        $password = (string) config('backup.backup.password');

        if ($password !== '') {
            $zip->setPassword($password);
        }

        $entry = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_starts_with($name, 'db-dumps/') && (str_ends_with($name, '.sql') || str_ends_with($name, '.sql.gz'))) {
                $entry = $name;
                break;
            }
        }

        if ($entry === null) {
            $zip->close();

            throw new RuntimeException('The backup archive holds no database dump.');
        }

        $contents = $zip->getFromName($entry);
        $zip->close();

        if ($contents === false) {
            throw new RuntimeException('The database dump could not be read: is BACKUP_ARCHIVE_PASSWORD the one the backup was made with?');
        }

        if (str_ends_with($entry, '.gz')) {
            $contents = gzdecode($contents);

            if ($contents === false) {
                throw new RuntimeException('The database dump could not be decompressed.');
            }
        }

        $dump = "{$work}/dump.sql";
        file_put_contents($dump, $contents);

        return $dump;
    }

    /**
     * @return array{backup: string, taken_at: CarbonInterface, tables: array<string, array{backup: int|null, live: int}>, names_readable: bool|null, problems: list<string>}
     */
    private function verify(string $path, CarbonInterface $takenAt): array
    {
        $restored = DB::connection(self::RESTORED);
        $tables = [];
        $problems = [];

        foreach (self::TABLES as $table) {
            $exists = $restored->getSchemaBuilder()->hasTable($table);
            $tables[$table] = [
                'backup' => $exists ? (int) $restored->table($table)->count() : null,
                'live' => DB::table($table)->count(),
            ];

            if (! $exists) {
                $problems[] = "The table \"{$table}\" is missing from the backup.";
            }
        }

        if (($tables['users']['backup'] ?? 0) === 0) {
            $problems[] = 'The backup has no users.';
        }

        $readable = null;

        if ($tables['case_parties']['backup'] ?? null) {
            $sample = $restored->table('case_parties')->whereNotNull('name')->value('name');

            try {
                Crypt::decryptString((string) $sample);
                $readable = true;
            } catch (DecryptException) {
                $readable = false;
                $problems[] = 'The names in the backup cannot be decrypted with this APP_KEY: keep the APP_KEY the backup was made with.';
            }
        }

        $maxAge = (int) config('courtwatch.backups.restore_check_max_age_days', 2);

        if ($takenAt->lt(now()->subDays($maxAge))) {
            $problems[] = "The newest backup is older than {$maxAge} days ({$takenAt->toDateTimeString()}).";
        }

        return ['backup' => $path, 'taken_at' => $takenAt, 'tables' => $tables, 'names_readable' => $readable, 'problems' => $problems];
    }

    private function dropDatabase(string $database): void
    {
        DB::connection(self::ADMIN)->statement("DROP DATABASE IF EXISTS \"{$database}\" WITH (FORCE)");
    }
}
