<?php

namespace App\Filament\Admin\Resources\Reference\Divisions;

use App\Filament\Admin\Resources\Reference\Divisions\Pages\ManageDivisions;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Department;
use App\Models\Reference\Division;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class DivisionResource extends ReferenceResource
{
    protected static ?string $model = Division::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reference/divisions';

    public static function key(): string
    {
        return 'divisions';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('department_id')->label(__('reference.fields.department'))->relationship('department', 'name_sq')->searchable()->preload(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('department.name_sq')->label(__('reference.fields.department'))->sortable(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'department' => ['rules' => ['nullable', 'string'], 'resolve' => static::resolver(Department::class, ['code', 'name_sq', 'name_en']), 'attribute' => 'department_id'],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDivisions::route('/'),
        ];
    }
}
