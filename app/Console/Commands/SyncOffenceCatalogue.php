<?php

namespace App\Console\Commands;

use App\Services\Reference\OffenceCatalogue;
use Illuminate\Console\Command;

class SyncOffenceCatalogue extends Command
{
    protected $signature = 'reference:sync-offences';

    protected $description = 'Bring the criminal codes, offences and statistics categories in line with database/data';

    public function handle(): int
    {
        $result = (new OffenceCatalogue)->sync();

        $this->info(__('reference.catalogue.synced', $result));

        return self::SUCCESS;
    }
}
