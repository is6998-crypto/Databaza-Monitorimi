<?php

namespace App\Filament\Admin\Resources\Hearings\Tables;

use App\Enums\HearingType;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Actions\TableActions;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Services\Export\ExportColumns;
use App\Services\Search\CaseSearch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HearingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['case.court', 'court', 'judge', 'monitor.user']))
            ->defaultSort('hearing_date', 'desc')
            ->columns([
                TextColumn::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                TextColumn::make('case_id')->label(__('hearings.fields.case'))
                    ->formatStateUsing(fn (Hearing $record): string => $record->case->display_label)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('case', fn (Builder $case) => CaseSearch::apply($case, $search)))
                    ->wrap(),
                TextColumn::make('hearing_type')->label(__('hearings.fields.hearing_type'))->badge()->placeholder('—')->toggleable(),
                TextColumn::make('judge.name')->label(__('hearings.fields.judge'))->placeholder('—')->toggleable(),
                TextColumn::make('monitor.user.name')->label(__('hearings.fields.monitor'))->placeholder('—')->toggleable(),
                IconColumn::make('was_postponed')->label(__('hearings.fields.was_postponed_short'))->boolean()->trueColor('warning')->falseColor('success'),
                IconColumn::make('started_late')->label(__('hearings.fields.started_late_short'))->boolean()->trueColor('warning')->falseColor('success')->toggleable(),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('cases.fields.status'))->options(WorkflowStatus::options())->multiple(),
                SelectFilter::make('court_id')->label(__('hearings.fields.court'))->options(fn (): array => Court::selectOptions(false))->searchable(),
                SelectFilter::make('judge_id')->label(__('hearings.fields.judge'))->options(fn (): array => Judge::selectOptions(false))->searchable(),
                SelectFilter::make('monitor_id')->label(__('hearings.fields.monitor'))->options(fn (): array => Monitor::selectOptions(false))->searchable(),
                SelectFilter::make('hearing_type')->label(__('hearings.fields.hearing_type'))->options(HearingType::options()),
                TernaryFilter::make('was_postponed')->label(__('hearings.fields.was_postponed')),
                TernaryFilter::make('started_late')->label(__('hearings.fields.started_late')),
                Filter::make('hearing_date')
                    ->schema([
                        DatePicker::make('from')->label(__('dashboard.filters.from')),
                        DatePicker::make('until')->label(__('dashboard.filters.until')),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('hearing_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('hearing_date', '<=', $date))),
            ], layout: FiltersLayout::AboveContentCollapsible)
            // A labelled button, so the filter row reads as a control rather than a lone icon.
            ->filtersTriggerAction(fn (Action $action): Action => $action->button()->outlined())
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'xl' => 4])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    TableActions::changeStatus(),
                    TableActions::exportSelected('hearings', fn (): array => ExportColumns::hearings(), ExportColumns::hearingRelations()),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Hearing $record): string => HearingResource::getUrl('view', ['record' => $record]));
    }
}
