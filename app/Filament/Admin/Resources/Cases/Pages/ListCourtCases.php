<?php

namespace App\Filament\Admin\Resources\Cases\Pages;

use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourtCases extends ListRecords
{
    protected static string $resource = CourtCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
