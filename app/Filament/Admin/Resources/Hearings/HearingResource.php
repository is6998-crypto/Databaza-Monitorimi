<?php

namespace App\Filament\Admin\Resources\Hearings;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Cases\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\Hearings\Pages\CreateHearing;
use App\Filament\Admin\Resources\Hearings\Pages\EditHearing;
use App\Filament\Admin\Resources\Hearings\Pages\ListHearings;
use App\Filament\Admin\Resources\Hearings\Pages\ViewHearing;
use App\Filament\Admin\Resources\Hearings\Schemas\HearingForm;
use App\Filament\Admin\Resources\Hearings\Schemas\HearingInfolist;
use App\Filament\Admin\Resources\Hearings\Tables\HearingsTable;
use App\Models\Hearing;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HearingResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Hearing::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'hearings';

    public static function getModelLabel(): string
    {
        return __('hearings.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hearings.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Hearing
            ? __('hearings.title', ['date' => $record->hearing_date->format('d.m.Y'), 'case' => $record->case->display_label])
            : static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return HearingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HearingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HearingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
        ];
    }

    /**
     * @return Builder<Hearing>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Hearing> $query */
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHearings::route('/'),
            'create' => CreateHearing::route('/create'),
            'view' => ViewHearing::route('/{record}'),
            'edit' => EditHearing::route('/{record}/edit'),
        ];
    }
}
