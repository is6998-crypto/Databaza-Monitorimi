<?php

namespace App\Filament\Admin\Resources\Researchers\Pages;

use App\Filament\Admin\Resources\Researchers\ResearcherProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListResearcherProfiles extends ListRecords
{
    protected static string $resource = ResearcherProfileResource::class;
}
