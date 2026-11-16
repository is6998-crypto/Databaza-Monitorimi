<?php

namespace App\Console\Commands;

use App\Services\Deadlines\DeadlineNotifier;
use App\Services\Deadlines\DeadlineTracker;
use Illuminate\Console\Command;

class CheckDeadlines extends Command
{
    protected $signature = 'deadlines:check {--no-notify : Recompute the time limits without sending notifications}';

    protected $description = 'Recompute the legal time limits of every criminal case and notify staff about the ones that were exceeded';

    public function handle(DeadlineTracker $tracker, DeadlineNotifier $notifier): int
    {
        $cases = $tracker->refreshAll();
        $this->info("Time limits recomputed for {$cases} criminal cases.");

        if ($this->option('no-notify')) {
            return self::SUCCESS;
        }

        $result = $notifier->notify();
        $this->info("{$result['deadlines']} newly exceeded time limits reported to {$result['recipients']} people.");

        return self::SUCCESS;
    }
}
