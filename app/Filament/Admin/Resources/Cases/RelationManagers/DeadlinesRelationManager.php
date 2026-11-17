<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\CaseType;
use App\Filament\Admin\Resources\Deadlines\Tables\CaseDeadlinesTable;
use App\Models\CourtCase;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Legal time limits of the phases this case has reached (criminal cases only). Read-only: the rows follow the
 * stage dates and the facts recorded on the case.
 */
class DeadlinesRelationManager extends RelationManager
{
    protected static string $relationship = 'deadlines';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedClock;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('deadlines.plural');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var CourtCase $ownerRecord */
        $overdue = $ownerRecord->deadlines()->overdue()->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'warning';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof CourtCase
            && in_array($ownerRecord->type, CaseType::criminal(), true)
            && Gate::allows('view', $ownerRecord);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_on')
            ->paginated(false)
            ->columns(CaseDeadlinesTable::columns())
            ->description(__('deadlines.case_help'))
            ->emptyStateHeading(__('deadlines.empty.case_heading'))
            ->emptyStateDescription(__('deadlines.empty.case_description'))
            ->emptyStateIcon(Heroicon::OutlinedClock);
    }
}
