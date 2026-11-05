<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creates the private bucket of every S3 disk the app uses (default disk, attachments), when it
 * does not exist yet. Run by the development stack on start; also handy on a new server.
 */
class EnsureStorageBucket extends Command
{
    protected $signature = 'app:ensure-bucket {--retries=10 : Attempts while the storage server starts}';

    protected $description = 'Create the S3 bucket(s) used for files when missing (private)';

    public function handle(): int
    {
        $disks = array_values(array_unique([(string) config('filesystems.default'), (string) config('courtwatch.attachments.disk')]));
        $s3 = array_values(array_filter($disks, fn (string $disk): bool => config("filesystems.disks.{$disk}.driver") === 's3'));

        if ($s3 === []) {
            $this->info('Files are stored on a local disk: no bucket needed.');

            return self::SUCCESS;
        }

        foreach ($s3 as $disk) {
            $adapter = Storage::disk($disk);
            $bucket = (string) config("filesystems.disks.{$disk}.bucket");

            if (! $adapter instanceof AwsS3V3Adapter || $bucket === '') {
                $this->error("Disk [{$disk}] is not a configured S3 disk.");

                return self::FAILURE;
            }

            $client = $adapter->getClient();
            $attempts = max(1, (int) $this->option('retries'));

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    if ($client->doesBucketExist($bucket)) {
                        $this->info("Bucket [{$bucket}] exists.");
                    } else {
                        $client->createBucket(['Bucket' => $bucket]);
                        $this->info("Bucket [{$bucket}] created (private).");
                    }

                    continue 2;
                } catch (Throwable $exception) {
                    if ($attempt === $attempts) {
                        $this->error("Cannot reach the storage for disk [{$disk}]: ".class_basename($exception).'.');

                        return self::FAILURE;
                    }

                    sleep(2);
                }
            }
        }

        return self::SUCCESS;
    }
}
