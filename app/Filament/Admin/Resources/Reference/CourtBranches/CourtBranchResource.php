<?php

namespace App\Filament\Admin\Resources\Reference\CourtBranches;

use App\Filament\Admin\Resources\Reference\CourtBranches\Pages\ManageCourtBranches;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\ReferenceModel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CourtBranchResource extends ReferenceResource
{
    protected static ?string $model = CourtBranch::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'reference/court-branches';

    public static function key(): string
    {
        return 'court_branches';
    }

    /**
     * Short enough for one line of the sidebar; the page title keeps the full name.
     */
    public static function getNavigationLabel(): string
    {
        return __('reference.court_branches.navigation');
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('court_id')->label(__('reference.fields.court'))->relationship('court', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload()->required(),
            TextInput::make('city')->label(__('reference.fields.city'))->maxLength(255),
            Select::make('departments')
                ->label(__('reference.fields.departments'))
                ->helperText(__('reference.help.departments'))
                ->relationship('departments', 'name_sq')
                ->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())
                ->multiple()
                ->preload()
                ->columnSpanFull(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('court.name_sq')->getStateUsing(fn ($record): ?string => $record->court?->label())->label(__('reference.fields.court'))->sortable()->searchable(),
            TextColumn::make('city')->label(__('reference.fields.city'))->toggleable(),
            TextColumn::make('departments_list')
                ->label(__('reference.fields.departments'))
                ->state(fn (CourtBranch $record): array => $record->departments->map(fn (ReferenceModel $department): string => $department->label())->all())
                ->badge()
                ->color('gray')
                ->wrap()
                ->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('court_id')->label(__('reference.fields.court'))->relationship('court', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'court' => ['rules' => ['required', 'string'], 'resolve' => static::resolver(Court::class, ['code', 'name_sq', 'name_en']), 'attribute' => 'court_id'],
            'city' => ['rules' => ['nullable', 'string', 'max:255']],
        ];
    }

    public static function csvMatchOn(): array
    {
        return ['court_id', 'name_sq'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCourtBranches::route('/'),
        ];
    }
}
