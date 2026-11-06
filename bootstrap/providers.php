<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ResearchPanelProvider;
use App\Providers\HorizonServiceProvider;
use Migration\MigrationServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ResearchPanelProvider::class,
    HorizonServiceProvider::class,
    MigrationServiceProvider::class,
];
