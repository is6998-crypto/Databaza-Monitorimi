<?php

namespace App\Filament\Admin\Resources\Reference\CriminalOffences\Pages;

use App\Filament\Admin\Resources\Reference\CriminalOffences\CriminalOffenceResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageCriminalOffences extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = CriminalOffenceResource::class;
}
