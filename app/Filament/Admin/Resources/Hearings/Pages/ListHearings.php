<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Actions\TableActions;
use App\Filament\Admin\Concerns\HasSavedFilters;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Services\Export\ExportColumns;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHearings extends ListRecords
{
    use HasSavedFilters;

    protected static string $resource = HearingResource::class;

    public static function savedFilterKey(): string
    {
        return 'hearings';
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->savedFilterActions(),
            TableActions::exportFiltered('hearings', fn (): array => ExportColumns::hearings(), ExportColumns::hearingRelations()),
            CreateAction::make()->label(__('hearings.actions.add')),
        ];
    }
}
