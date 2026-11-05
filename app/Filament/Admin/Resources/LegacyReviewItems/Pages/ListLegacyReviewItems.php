<?php

namespace App\Filament\Admin\Resources\LegacyReviewItems\Pages;

use App\Filament\Admin\Resources\LegacyReviewItems\LegacyReviewItemResource;
use Filament\Resources\Pages\ListRecords;

class ListLegacyReviewItems extends ListRecords
{
    protected static string $resource = LegacyReviewItemResource::class;
}
