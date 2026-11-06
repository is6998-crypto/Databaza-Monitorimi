<?php

namespace App\Filament\Admin\Resources\Reference\Judges;

use App\Filament\Admin\Resources\Reference\Judges\Pages\ManageJudges;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class JudgeResource extends ReferenceResource
{
    protected static ?string $model = Judge::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'reference/judges';

    public static function key(): string
    {
        return 'judges';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('court_id')->label(__('reference.fields.court'))->relationship('court', 'name_sq')->searchable()->preload(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('court.name_sq')->label(__('reference.fields.court'))->sortable()->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('court_id')->label(__('reference.fields.court'))->relationship('court', 'name_sq')->searchable()->preload(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'court' => ['rules' => ['nullable', 'string'], 'resolve' => static::resolver(Court::class, ['code', 'name_sq', 'name_en']), 'attribute' => 'court_id'],
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJudges::route('/'),
        ];
    }
}
