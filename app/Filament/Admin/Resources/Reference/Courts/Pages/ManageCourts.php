<?php

namespace App\Filament\Admin\Resources\Reference\Courts\Pages;

use App\Filament\Admin\Resources\Reference\Courts\CourtResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageCourts extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = CourtResource::class;
}
