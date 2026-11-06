<?php

namespace App\Filament\Admin\Resources\Reference;

use App\Filament\Admin\Actions\ImportCsvAction;
use Filament\Actions\CreateAction;

/**
 * Header actions shared by every reference-table page: create, CSV import and template.
 */
trait ManagesReferenceRecords
{
    protected function getHeaderActions(): array
    {
        /** @var class-string<ReferenceResource> $resource */
        $resource = static::getResource();
        $importer = $resource::csvImporter();

        return [
            CreateAction::make(),
            ImportCsvAction::make($resource::referenceModel(), $importer),
            ImportCsvAction::template($resource::key(), $importer),
        ];
    }
}
