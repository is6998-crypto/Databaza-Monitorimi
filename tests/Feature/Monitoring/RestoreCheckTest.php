<?php

use App\Services\Monitoring\RestoreCheck;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;

/*
| backup:restore-check: the newest backup is unpacked, loaded into a temporary database with psql and checked.
| Needs the psql client (as in the production image); skipped where it is not installed.
*/

beforeEach(function () {
    if ((new ExecutableFinder)->find('psql') === null) {
        $this->markTestSkipped('psql is not installed.');
    }

    $this->root = storage_path('framework/testing/restore-check-'.bin2hex(random_bytes(4)));
    $this->database = 'cw_restore_check_'.bin2hex(random_bytes(4));

    config([
        'filesystems.disks.restore_test' => ['driver' => 'local', 'root' => $this->root],
        'backup.backup.name' => 'courtwatch',
        'backup.backup.password' => 'backup-secret',
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

/**
 * Writes a backup archive like the backup package does: an AES-256 zip with db-dumps/postgresql-<db>.sql.gz.
 *
 * @param  list<string>  $tables
 */
function makeBackup(string $root, string $password = 'backup-secret', ?string $encryptedName = null, array $tables = []): void
{
    $tables = $tables ?: ['users', 'cases', 'case_parties', 'hearings', 'hearing_reports', 'scheduled_hearings', 'case_deadlines', 'activity_log'];
    $encryptedName ??= Crypt::encryptString('Arben Testaj');

    $sql = '';
    foreach ($tables as $table) {
        $sql .= $table === 'case_parties'
            ? "CREATE TABLE case_parties (id bigint primary key, name text);\nINSERT INTO case_parties VALUES (1, '{$encryptedName}');\n"
            : "CREATE TABLE {$table} (id bigint primary key);\n";
    }
    $sql .= in_array('users', $tables, true) ? "INSERT INTO users VALUES (1), (2);\n" : '';
    $sql .= in_array('cases', $tables, true) ? "INSERT INTO cases VALUES (1), (2), (3);\n" : '';

    File::ensureDirectoryExists("{$root}/courtwatch");
    $zip = new ZipArchive;
    $zip->open("{$root}/courtwatch/2026-11-27-02-00-00.zip", ZipArchive::CREATE);
    $zip->addFromString('db-dumps/postgresql-courtwatch.sql.gz', (string) gzencode($sql));
    $zip->setEncryptionName('db-dumps/postgresql-courtwatch.sql.gz', ZipArchive::EM_AES_256, $password);
    $zip->close();
}

function databaseExists(string $name): bool
{
    return DB::table('pg_database')->where('datname', $name)->exists();
}

it('restores the newest backup into a temporary database, checks it and removes it', function () {
    makeBackup($this->root);

    $this->artisan('backup:restore-check', ['--disk' => 'restore_test', '--database' => $this->database])
        ->expectsOutputToContain('restored into a temporary database')
        ->expectsTable(['Table', 'Rows in the backup', 'Rows now'], collect(RestoreCheck::TABLES)->map(fn (string $table): array => [
            $table,
            match ($table) {
                'users' => 2, 'cases' => 3, 'case_parties' => 1, default => 0
            },
            DB::table($table)->count(),
        ])->all())
        ->expectsOutputToContain('Encrypted names are readable with this APP_KEY.')
        ->expectsOutputToContain('The backup can be restored.')
        ->assertSuccessful();

    expect(databaseExists($this->database))->toBeFalse()
        ->and(File::glob(storage_path('app/backup-temp/restore-check-*')))->toBe([]);
});

it('fails when the backup was made with another APP_KEY', function () {
    $other = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');
    makeBackup($this->root, encryptedName: $other->encryptString('Arben Testaj'));

    $this->artisan('backup:restore-check', ['--disk' => 'restore_test', '--database' => $this->database])
        ->expectsOutputToContain('cannot be decrypted with this APP_KEY')
        ->assertFailed();

    expect(databaseExists($this->database))->toBeFalse();
});

it('fails when a table is missing, the password is wrong or there is no backup', function (Closure $prepare, string $message) {
    $prepare($this->root);

    $this->artisan('backup:restore-check', ['--disk' => 'restore_test', '--database' => $this->database])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(databaseExists($this->database))->toBeFalse();
})->with([
    'missing table' => [fn (string $root) => makeBackup($root, tables: ['users', 'cases', 'case_parties']), 'The table "hearings" is missing from the backup.'],
    'wrong password' => [fn (string $root) => makeBackup($root, password: 'another-secret'), 'is BACKUP_ARCHIVE_PASSWORD the one the backup was made with?'],
    'no backup' => [fn (string $root) => null, 'No backup found on the disk "restore_test".'],
]);
