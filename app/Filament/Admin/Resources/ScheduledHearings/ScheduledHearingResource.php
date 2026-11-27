<?php

namespace App\Filament\Admin\Resources\ScheduledHearings;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\CreateScheduledHearing;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\EditScheduledHearing;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\HearingCalendar;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\ListScheduledHearings;
use App\Filament\Admin\Resources\ScheduledHearings\Schemas\ScheduledHearingForm;
use App\Filament\Admin\Resources\ScheduledHearings\Tables\ScheduledHearingsTable;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The hearing calendar: court sessions that are coming up and who attends them. Opens on the month view; the
 * list beside it is for planning and finding gaps.
 */
class ScheduledHearingResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = ScheduledHearing::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'calendar';

    public static function getModelLabel(): string
    {
        return __('calendar.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('calendar.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('calendar.navigation');
    }

    /**
     * Hearings this week that nobody attends yet, among the cases this person may see.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->upcoming()->whereNull('monitor_id')
            ->whereDate('scheduled_on', '<=', ScheduledHearing::today()->addDays(7)->toDateString())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('calendar.badge');
    }

    public static function form(Schema $schema): Schema
    {
        return ScheduledHearingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledHearingsTable::configure($table);
    }

    /**
     * @return Builder<ScheduledHearing>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<ScheduledHearing> $query */
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
            'index' => HearingCalendar::route('/'),
            'list' => ListScheduledHearings::route('/list'),
            'create' => CreateScheduledHearing::route('/create'),
            'edit' => EditScheduledHearing::route('/{record}/edit'),
        ];
    }
}
