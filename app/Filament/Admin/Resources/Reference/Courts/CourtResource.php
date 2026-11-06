<?php

namespace App\Filament\Admin\Resources\Reference\Courts;

use App\Enums\CourtLevel;
use App\Filament\Admin\Resources\Reference\Courts\Pages\ManageCourts;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Court;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Validation\Rule;

class CourtResource extends ReferenceResource
{
    protected static ?string $model = Court::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reference/courts';

    public static function key(): string
    {
        return 'courts';
    }

    protected static function extraFormFields(): array
    {
        return [
            TextInput::make('code')->label(__('reference.fields.code'))->maxLength(32)->unique(ignoreRecord: true),
            Select::make('level')->label(__('reference.fields.level'))->options(CourtLevel::options())->required(),
            TextInput::make('city')->label(__('reference.fields.city'))->maxLength(255),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
            TextColumn::make('level')->label(__('reference.fields.level'))->badge()->sortable(),
            TextColumn::make('city')->label(__('reference.fields.city'))->searchable()->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('level')->label(__('reference.fields.level'))->options(CourtLevel::options()),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'code' => ['rules' => ['nullable', 'string', 'max:32']],
            'level' => ['rules' => ['required', Rule::in(CourtLevel::values())]],
            'city' => ['rules' => ['nullable', 'string', 'max:255']],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCourts::route('/'),
        ];
    }
}
