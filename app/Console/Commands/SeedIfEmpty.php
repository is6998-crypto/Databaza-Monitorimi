<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedIfEmpty extends Command
{
    protected $signature = 'app:seed-if-empty';

    protected $description = 'Seed reference data (and demo data outside production) when the database is empty';

    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->error('Run the migrations first.');

            return self::FAILURE;
        }

        if (User::query()->exists()) {
            $this->info('Database already seeded — skipping.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
