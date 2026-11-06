<?php

namespace App\Filament\Admin\Resources\Reference\Nationalities\Pages;

use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use App\Filament\Admin\Resources\Reference\Nationalities\NationalityResource;
use Filament\Resources\Pages\ManageRecords;

class ManageNationalities extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = NationalityResource::class;
}
