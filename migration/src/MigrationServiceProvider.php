<?php

namespace Migration;

use Illuminate\Support\ServiceProvider;
use Migration\Console\ImportLegacyCommand;
use Migration\Console\InspectLegacyCommand;
use Migration\Console\MakeLegacyFixtureCommand;

/**
 * Legacy migration toolkit (proposal B8): mapping config and the legacy:* commands.
 */
class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('migration/config/legacy.php'), 'legacy');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportLegacyCommand::class,
                InspectLegacyCommand::class,
                MakeLegacyFixtureCommand::class,
            ]);
        }
    }
}
