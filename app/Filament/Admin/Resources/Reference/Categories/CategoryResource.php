<?php

namespace App\Filament\Admin\Resources\Reference\Categories;

use App\Filament\Admin\Resources\Reference\Categories\Pages\ManageCategories;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Category;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

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

    protected static function extraFormFields(): array
    {
        return [
            TextInput::make('code')->label(__('reference.fields.code'))->maxLength(16)->unique(ignoreRecord: true),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('reference.fields.code'))->badge()->searchable()->sortable(),
            TextColumn::make('offences_count')->counts('offences')->label(__('reference.fields.offences_count'))->numeric()->sortable(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'code' => ['rules' => ['nullable', 'string', 'max:16']],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
