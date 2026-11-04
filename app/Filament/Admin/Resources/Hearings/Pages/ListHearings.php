<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Resources\Hearings\HearingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHearings extends ListRecords
{
    protected static string $resource = HearingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('hearings.actions.add'))];
    }
}
