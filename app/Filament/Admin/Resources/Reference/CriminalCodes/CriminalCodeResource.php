<?php

namespace App\Filament\Admin\Resources\Reference\CriminalCodes;

use App\Filament\Admin\Resources\Reference\CriminalCodes\Pages\ManageCriminalCodes;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\CriminalCode;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class CriminalCodeResource extends ReferenceResource
{
    protected static ?string $model = CriminalCode::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'reference/criminal-codes';

    public static function key(): string
    {
        return 'criminal_codes';
    }

    protected static function extraFormFields(): array
    {
        return [
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->unique(ignoreRecord: true),
            TextInput::make('year')->label(__('reference.fields.year'))->numeric()->minValue(1950)->maxValue(2100),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
            TextColumn::make('year')->label(__('reference.fields.year'))->sortable(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'code' => ['rules' => ['required', 'string', 'max:32']],
            'year' => ['rules' => ['nullable', 'integer', 'min:1950', 'max:2100']],
        ];
    }

    public static function csvMatchOn(): array
    {
        return ['code'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCriminalCodes::route('/'),
        ];
    }
}
