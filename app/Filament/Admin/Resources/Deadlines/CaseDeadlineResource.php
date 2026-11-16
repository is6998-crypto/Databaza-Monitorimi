<?php

namespace App\Filament\Admin\Resources\Deadlines;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Deadlines\Pages\ListCaseDeadlines;
use App\Filament\Admin\Resources\Deadlines\Tables\CaseDeadlinesTable;
use App\Models\CaseDeadline;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "Legal time limits": every phase of a criminal case that has a limit in the Criminal Procedure Code, with the
 * ones that were exceeded first. Read-only: the rows follow the stages recorded on the cases.
 */
class CaseDeadlineResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = CaseDeadline::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'legal-deadlines';

    public static function getModelLabel(): string
    {
        return __('deadlines.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('deadlines.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    /**
     * Number of limits exceeded right now, among the cases this person may see.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->overdue()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('deadlines.tabs.overdue');
    }

    public static function table(Table $table): Table
    {
        return CaseDeadlinesTable::configure($table);
    }

    /**
     * @return Builder<CaseDeadline>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<CaseDeadline> $query */
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
            'index' => ListCaseDeadlines::route('/'),
        ];
    }
}
