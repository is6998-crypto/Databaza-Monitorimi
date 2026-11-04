<?php

namespace App\Filament\Admin\Resources\ExportLogs\Pages;

use App\Filament\Admin\Resources\ExportLogs\ExportLogResource;
use Filament\Resources\Pages\ListRecords;

class ListExportLogs extends ListRecords
{
    protected static string $resource = ExportLogResource::class;
}
