<?php

namespace App\Filament\Admin\Resources\Reference\CriminalCodes\Pages;

use App\Filament\Admin\Resources\Reference\CriminalCodes\CriminalCodeResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageCriminalCodes extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = CriminalCodeResource::class;
}
