<?php

namespace App\Filament\Admin\Resources\Researchers\Pages;

use App\Filament\Admin\Resources\Researchers\ResearcherProfileResource;
use Filament\Resources\Pages\ViewRecord;

class ViewResearcherProfile extends ViewRecord
{
    protected static string $resource = ResearcherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return ResearcherProfileResource::reviewActions();
    }
}
