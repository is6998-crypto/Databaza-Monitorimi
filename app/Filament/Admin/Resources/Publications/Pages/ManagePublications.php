<?php

namespace App\Filament\Admin\Resources\Publications\Pages;

use App\Filament\Admin\Resources\Publications\PublicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePublications extends ManageRecords
{
    protected static string $resource = PublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(fn (array $data): array => [
                ...$data,
                'file_disk' => filled($data['file_path'] ?? null) ? 'local' : null,
                'created_by' => auth()->id(),
            ]),
        ];
    }
}
