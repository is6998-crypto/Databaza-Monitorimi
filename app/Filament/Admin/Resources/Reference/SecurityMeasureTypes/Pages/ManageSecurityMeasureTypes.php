<?php

namespace App\Filament\Admin\Resources\Reference\SecurityMeasureTypes\Pages;

use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use App\Filament\Admin\Resources\Reference\SecurityMeasureTypes\SecurityMeasureTypeResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSecurityMeasureTypes extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = SecurityMeasureTypeResource::class;
}
