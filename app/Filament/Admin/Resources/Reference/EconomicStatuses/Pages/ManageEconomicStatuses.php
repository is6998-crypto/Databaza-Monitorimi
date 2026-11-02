<?php

namespace App\Filament\Admin\Resources\Reference\EconomicStatuses\Pages;

use App\Filament\Admin\Resources\Reference\EconomicStatuses\EconomicStatusResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageEconomicStatuses extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = EconomicStatusResource::class;
}
