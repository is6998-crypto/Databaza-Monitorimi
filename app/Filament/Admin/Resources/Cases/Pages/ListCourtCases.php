<?php

namespace App\Filament\Admin\Resources\Cases\Pages;

use App\Filament\Admin\Actions\TableActions;
use App\Filament\Admin\Concerns\HasSavedFilters;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Services\Export\ExportColumns;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourtCases extends ListRecords
{
    use HasSavedFilters;

    protected static string $resource = CourtCaseResource::class;

    public static function savedFilterKey(): string
    {
        return 'cases';
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->savedFilterActions(),
            TableActions::exportFiltered('cases', fn (): array => ExportColumns::cases(), ExportColumns::caseRelations()),
            CreateAction::make(),
        ];
    }
}
