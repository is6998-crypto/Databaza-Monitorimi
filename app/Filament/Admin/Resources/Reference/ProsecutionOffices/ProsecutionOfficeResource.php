<?php

namespace App\Filament\Admin\Resources\Reference\ProsecutionOffices;

use App\Enums\ProsecutionLevel;
use App\Filament\Admin\Resources\Reference\ProsecutionOffices\Pages\ManageProsecutionOffices;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\ProsecutionOffice;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Validation\Rule;

class ProsecutionOfficeResource extends ReferenceResource
{
    protected static ?string $model = ProsecutionOffice::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'reference/prosecution-offices';

    public static function key(): string
    {
        return 'prosecution_offices';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('level')->label(__('reference.fields.level'))->options(ProsecutionLevel::options())->required(),
            TextInput::make('city')->label(__('reference.fields.city'))->maxLength(255),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('level')->label(__('reference.fields.level'))->badge()->sortable(),
            TextColumn::make('city')->label(__('reference.fields.city'))->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('level')->label(__('reference.fields.level'))->options(ProsecutionLevel::options()),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'level' => ['rules' => ['required', Rule::in(ProsecutionLevel::values())]],
            'city' => ['rules' => ['nullable', 'string', 'max:255']],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProsecutionOffices::route('/'),
        ];
    }
}
