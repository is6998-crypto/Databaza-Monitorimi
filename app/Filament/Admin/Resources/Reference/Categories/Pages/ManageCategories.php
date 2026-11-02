<?php

namespace App\Filament\Admin\Resources\Reference\Categories\Pages;

use App\Filament\Admin\Resources\Reference\Categories\CategoryResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = CategoryResource::class;
}
