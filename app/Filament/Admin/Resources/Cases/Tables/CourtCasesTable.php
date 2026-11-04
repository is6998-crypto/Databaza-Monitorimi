<?php

namespace App\Filament\Admin\Resources\Cases\Tables;

use App\Enums\CaseType;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Models\CourtCase;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourtCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['court', 'judge', 'assignedMonitor.user'])->withCount('hearings'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('court_case_number')
                    ->label(__('cases.fields.court_case_number'))
                    ->placeholder('—')
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (CourtCase $record): ?string => $record->indictment_number),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge()->sortable(),
                TextColumn::make('type')->label(__('cases.fields.type'))->badge()->sortable(),
                TextColumn::make('court_id')
                    ->label(__('cases.fields.court'))
                    ->formatStateUsing(fn (CourtCase $record): string => $record->court?->label() ?? '—')
                    ->sortable(),
                TextColumn::make('judge.name')->label(__('cases.fields.judge'))->toggleable(),
                TextColumn::make('assignedMonitor.user.name')->label(__('cases.fields.assigned_monitor'))->toggleable(),
                TextColumn::make('hearings_count')->label(__('cases.fields.hearings_count'))->numeric()->sortable()->alignEnd(),
                TextColumn::make('updated_at')->label(__('cases.fields.updated_at'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('cases.fields.type'))->options(CaseType::options())->multiple(),
                SelectFilter::make('status')->label(__('cases.fields.status'))->options(WorkflowStatus::options())->multiple(),
                SelectFilter::make('court_id')->label(__('cases.fields.court'))->options(fn (): array => Court::selectOptions(false))->multiple()->searchable(),
                SelectFilter::make('judge_id')->label(__('cases.fields.judge'))->options(fn (): array => Judge::selectOptions(false))->searchable(),
                SelectFilter::make('assigned_monitor_id')->label(__('cases.fields.assigned_monitor'))->options(fn (): array => Monitor::selectOptions(false))->searchable(),
                TernaryFilter::make('mine')
                    ->label(__('cases.filters.mine'))
                    ->queries(
                        true: fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('created_by', auth()->id())->orWhere('assigned_monitor_id', auth()->user()?->monitor?->id)),
                        false: fn (Builder $query) => $query,
                    ),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label(__('cases.filters.created_from')),
                        DatePicker::make('created_until')->label(__('cases.filters.created_until')),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('cases.created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('cases.created_at', '<=', $date))),
                TrashedFilter::make()->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'xl' => 4])
            ->recordActions([
                ViewAction::make()->label(__('cases.actions.open')),
                EditAction::make(),
            ])
            ->recordUrl(fn (CourtCase $record): string => CourtCaseResource::getUrl('view', ['record' => $record]));
    }
}
