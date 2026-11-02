<?php

namespace App\Filament\Admin\Resources\Reference\Categories;

use App\Filament\Admin\Resources\Reference\Categories\Pages\ManageCategories;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Category;
use Filament\Support\Icons\Heroicon;

class CategoryResource extends ReferenceResource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'reference/categories';

    public static function key(): string
    {
        return 'categories';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
