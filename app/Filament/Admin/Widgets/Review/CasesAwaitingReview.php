<?php

namespace App\Filament\Admin\Widgets\Review;

use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Models\CourtCase;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Editor review queue — cases submitted for review.
 */
class CasesAwaitingReview extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('workflow.queue.cases'))
            ->query(fn () => CourtCase::query()->awaitingReview()->with(['court', 'creator']))
            ->defaultSort('submitted_at')
            ->columns([
                TextColumn::make('court_case_number')->label(__('cases.fields.court_case_number'))->placeholder('—')->weight('medium'),
                TextColumn::make('type')->label(__('cases.fields.type'))->badge(),
                TextColumn::make('court_id')->label(__('cases.fields.court'))->formatStateUsing(fn (CourtCase $record): string => $record->court?->label() ?? '—'),
                TextColumn::make('creator.name')->label(__('workflow.queue.submitted_by'))->placeholder('—'),
                TextColumn::make('submitted_at')->label(__('workflow.queue.submitted_at'))->dateTime()->sortable(),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge(),
            ])
            ->recordUrl(fn (CourtCase $record): string => CourtCaseResource::getUrl('view', ['record' => $record]))
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
