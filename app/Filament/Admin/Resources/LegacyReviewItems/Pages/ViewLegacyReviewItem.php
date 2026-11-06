<?php

namespace App\Filament\Admin\Resources\LegacyReviewItems\Pages;

use App\Filament\Admin\Resources\LegacyReviewItems\LegacyReviewItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLegacyReviewItem extends ViewRecord
{
    protected static string $resource = LegacyReviewItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LegacyReviewItemResource::openCaseAction(),
            LegacyReviewItemResource::resolveAction(),
        ];
    }
}
