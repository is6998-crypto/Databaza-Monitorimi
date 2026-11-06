<?php

namespace App\Filament\Admin\Resources\Reference\Departments;

use App\Filament\Admin\Resources\Reference\Departments\Pages\ManageDepartments;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Department;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class DepartmentResource extends ReferenceResource
{
    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'reference/departments';

    public static function key(): string
    {
        return 'departments';
    }

    protected static function extraFormFields(): array
    {
        return [
            TextInput::make('code')->label(__('reference.fields.code'))->maxLength(32),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'code' => ['rules' => ['nullable', 'string', 'max:32']],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }
}
