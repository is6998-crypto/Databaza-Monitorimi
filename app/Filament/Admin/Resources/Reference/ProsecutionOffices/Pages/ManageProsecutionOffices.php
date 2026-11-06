<?php

namespace App\Filament\Admin\Resources\Reference\ProsecutionOffices\Pages;

use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use App\Filament\Admin\Resources\Reference\ProsecutionOffices\ProsecutionOfficeResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProsecutionOffices extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = ProsecutionOfficeResource::class;
}
