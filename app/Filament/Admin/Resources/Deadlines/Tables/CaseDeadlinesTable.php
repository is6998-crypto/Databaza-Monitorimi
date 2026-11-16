<?php

namespace App\Filament\Admin\Resources\Deadlines\Tables;

use App\Enums\DeadlineRule;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\Reference\Court;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Legal time limits, as a read-only list. Shared by the "Legal time limits" page (all cases) and the tab of the
 * same name inside a case.
 */
class CaseDeadlinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['case.court']))
            ->defaultSort('due_on')
            ->columns([
                CaseColumn::make('case_id', fn (Model $record): ?CourtCase => $record instanceof CaseDeadline ? $record->case : null),
                ...static::columns(),
            ])
            ->filters([
                SelectFilter::make('rule')->label(__('deadlines.fields.phase'))->options(DeadlineRule::options())->multiple(),
                SelectFilter::make('court')
                    ->label(__('cases.fields.court'))
                    ->options(fn (): array => Court::selectOptions(false))
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $court) => $query->whereHas('case', fn (Builder $case) => $case->where('court_id', $court)))),
                TernaryFilter::make('mine')
                    ->label(__('cases.filters.mine'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('case', fn (Builder $case) => $case->where(fn (Builder $case) => $case->where('created_by', auth()->id())->orWhere('assigned_monitor_id', auth()->user()?->monitor?->id))),
                        false: fn (Builder $query) => $query,
                    ),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(fn (Action $action): Action => $action->button()->outlined())
            ->filtersFormColumns(['default' => 1, 'sm' => 3])
            ->recordActions([
                Action::make('openCase')
                    ->label(__('deadlines.actions.open_case'))
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->url(fn (CaseDeadline $record): string => CourtCaseResource::getUrl('view', ['record' => $record->case_id]))
                    ->visible(fn (CaseDeadline $record): bool => Gate::allows('view', $record->case)),
            ])
            ->recordUrl(fn (CaseDeadline $record): ?string => Gate::allows('view', $record->case) ? CourtCaseResource::getUrl('view', ['record' => $record->case_id]) : null)
            ->emptyStateHeading(__('deadlines.empty.heading'))
            ->emptyStateDescription(__('deadlines.empty.description'))
            ->emptyStateIcon(Heroicon::OutlinedClock);
    }

    /**
     * Columns that describe the phase itself (without the case).
     *
     * @return list<Column>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('rule')
                ->label(__('deadlines.fields.phase'))
                ->description(fn (CaseDeadline $record): string => __('deadlines.article', ['article' => $record->rule->article()]))
                ->weight('medium'),
            TextColumn::make('started_on')
                ->label(__('deadlines.fields.started_on'))
                ->date()
                ->description(fn (CaseDeadline $record): string => $record->start_stage->getLabel())
                ->sortable(),
            TextColumn::make('limit_amount')
                ->label(__('deadlines.fields.limit'))
                ->formatStateUsing(fn (CaseDeadline $record): string => $record->limitLabel())
                ->description(fn (CaseDeadline $record): ?string => $record->basisLabel()),
            TextColumn::make('due_on')
                ->label(__('deadlines.fields.due_on'))
                ->date()
                ->description(fn (CaseDeadline $record): ?string => $record->ended_on ? __('deadlines.ended_on', ['date' => $record->ended_on->format((string) config('courtwatch.date_format'))]) : null)
                ->sortable(),
            TextColumn::make('state')
                ->label(__('deadlines.fields.status'))
                ->formatStateUsing(fn (CaseDeadline $record): string => $record->statusLabel())
                ->badge()
                ->color(fn (CaseDeadline $record): string => $record->statusColor()),
        ];
    }
}
