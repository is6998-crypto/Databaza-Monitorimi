<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHearing extends ViewRecord
{
    protected static string $resource = HearingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...WorkflowActions::all(),
        ];
    }
}
