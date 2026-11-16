<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Filament\Admin\Resources\Deadlines\Tables\CaseDeadlinesTable;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard: the legal time limits that are exceeded right now, the longest overdue first. Shown to the people
 * who are notified about them (monitors see their own courts and cases); hidden when there is nothing to show.
 */
class OverdueDeadlinesWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && Gate::allows('viewAny', CaseDeadline::class)
            && CaseDeadlineResource::getEloquentQuery()->overdue()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('deadlines.widget.title'))
            ->description(__('deadlines.widget.description'))
            ->query(fn (): Builder => CaseDeadlineResource::getEloquentQuery()->overdue()->with('case.court'))
            ->defaultSort('due_on')
            ->columns([
                CaseColumn::make('case_id', fn (Model $record): ?CourtCase => $record instanceof CaseDeadline ? $record->case : null),
                // Without the start date: the dashboard shows what is late and by how much; the list has the rest.
                ...array_values(array_filter(CaseDeadlinesTable::columns(), fn (Column $column): bool => $column->getName() !== 'started_on')),
            ])
            ->headerActions([
                Action::make('allDeadlines')
                    ->label(__('deadlines.widget.all'))
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->iconPosition('after')
                    ->link()
                    ->url(CaseDeadlineResource::getUrl('index')),
            ])
            ->recordUrl(fn (CaseDeadline $record): string => CourtCaseResource::getUrl('view', ['record' => $record->case_id]))
            ->emptyStateHeading(__('deadlines.widget.empty'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
