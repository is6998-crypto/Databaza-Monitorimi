<?php

namespace App\Filament\Admin\Resources\Reference\Prosecutors\Pages;

use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use App\Filament\Admin\Resources\Reference\Prosecutors\ProsecutorResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProsecutors extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = ProsecutorResource::class;
}
