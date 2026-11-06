<?php

namespace App\Filament\Admin\Resources\Reference\Monitors\Pages;

use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use App\Filament\Admin\Resources\Reference\Monitors\MonitorResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMonitors extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = MonitorResource::class;
}
