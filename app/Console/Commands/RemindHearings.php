<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarNotifier;
use Illuminate\Console\Command;

class RemindHearings extends Command
{
    protected $signature = 'hearings:remind';

    protected $description = 'Remind monitors of the hearings they attend tomorrow, and tell editors about high-priority hearings nobody attends';

    public function handle(CalendarNotifier $notifier): int
    {
        $reminded = $notifier->remind();
        $this->info("{$reminded['hearings']} hearings tomorrow: {$reminded['recipients']} monitors reminded.");

        $unstaffed = $notifier->warnUnstaffed();
        $this->info("{$unstaffed['hearings']} high-priority hearings without a monitor reported to {$unstaffed['recipients']} people.");

        return self::SUCCESS;
    }
}
