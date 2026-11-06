<?php

namespace App\Filament\Admin\Resources\Reference\Divisions\Pages;

use App\Filament\Admin\Resources\Reference\Divisions\DivisionResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageDivisions extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = DivisionResource::class;
}
