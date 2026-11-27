<?php

namespace App\Filament\Admin\Widgets\Review;

use App\Enums\CasePriority;
use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CourtCase;
use App\Models\Hearing;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Editor review queue — hearings submitted for review (from the panel or the monitor app).
 */
class HearingsAwaitingReview extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('workflow.queue.hearings'))
            ->query(fn () => Hearing::query()->awaitingReview()->with(['case.court', 'monitor.user']))
            // Submissions for high-priority cases first, then the oldest.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByRaw('(select '.CasePriority::orderSql('cases.priority').' from cases where cases.id = hearings.case_id)')->orderBy('hearings.submitted_at'))
            ->columns([
                TextColumn::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                CaseColumn::make('case_id', fn (Model $record): ?CourtCase => $record instanceof Hearing ? $record->case : null),
                TextColumn::make('case_priority')->label(__('cases.fields.priority'))
                    ->state(fn (Hearing $record): ?CasePriority => $record->case->priority === CasePriority::Normal ? null : $record->case->priority)
                    ->badge(),
                TextColumn::make('monitor.user.name')->label(__('hearings.fields.monitor'))->placeholder('—'),
                IconColumn::make('was_postponed')->label(__('hearings.fields.was_postponed_short'))->boolean()->trueColor('warning')->falseColor('success'),
                TextColumn::make('submitted_at')->label(__('workflow.queue.submitted_at'))->date()->sortable(),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge(),
            ])
            ->recordUrl(fn (Hearing $record): string => HearingResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    WorkflowActions::changes(),
                    WorkflowActions::startReview(),
                    WorkflowActions::approve(),
                    WorkflowActions::returnForCorrection(),
                ])->label(__('workflow.queue.review'))->button()->size('sm'),
            ])
            ->emptyStateHeading(__('workflow.queue.empty'));
    }
}
