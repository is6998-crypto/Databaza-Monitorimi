<?php

namespace App\Filament\Admin\Resources\Reference\Prosecutors;

use App\Filament\Admin\Resources\Reference\Prosecutors\Pages\ManageProsecutors;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use App\Models\Reference\ReferenceModel;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ProsecutorResource extends ReferenceResource
{
    protected static ?string $model = Prosecutor::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'reference/prosecutors';

    public static function key(): string
    {
        return 'prosecutors';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('prosecution_office_id')->label(__('reference.fields.prosecution_office'))->relationship('prosecutionOffice', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('prosecutionOffice.name_sq')->getStateUsing(fn ($record): ?string => $record->prosecutionOffice?->label())->label(__('reference.fields.prosecution_office'))->sortable()->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('prosecution_office_id')->label(__('reference.fields.prosecution_office'))->relationship('prosecutionOffice', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'prosecution_office' => ['rules' => ['nullable', 'string'], 'resolve' => static::resolver(ProsecutionOffice::class, ['name_sq', 'name_en']), 'attribute' => 'prosecution_office_id'],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProsecutors::route('/'),
        ];
    }
}
