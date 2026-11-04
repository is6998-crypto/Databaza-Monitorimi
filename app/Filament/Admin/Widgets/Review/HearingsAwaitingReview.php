<?php

namespace App\Filament\Admin\Widgets\Review;

use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

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
            ->defaultSort('submitted_at')
            ->columns([
                TextColumn::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                TextColumn::make('case_id')->label(__('hearings.fields.case'))->formatStateUsing(fn (Hearing $record): string => $record->case->display_label)->wrap(),
                TextColumn::make('monitor.user.name')->label(__('hearings.fields.monitor'))->placeholder('—'),
                IconColumn::make('was_postponed')->label(__('hearings.fields.was_postponed_short'))->boolean()->trueColor('warning')->falseColor('success'),
                TextColumn::make('submitted_at')->label(__('workflow.queue.submitted_at'))->dateTime()->sortable(),
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
