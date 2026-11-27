<?php

namespace App\Filament\Admin\Widgets\Review;

use App\Enums\CasePriority;
use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CourtCase;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
            // High-priority cases first, then the oldest submission.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByRaw(CasePriority::orderSql('cases.priority'))->orderBy('cases.submitted_at'))
            ->columns([
                CaseColumn::make('court_case_number', fn (Model $record): ?CourtCase => $record instanceof CourtCase ? $record : null),
                TextColumn::make('priority')->label(__('cases.fields.priority'))
                    ->state(fn (CourtCase $record): ?CasePriority => $record->priority === CasePriority::Normal ? null : $record->priority)
                    ->badge(),
                TextColumn::make('type')->label(__('cases.fields.type'))->badge(),
                TextColumn::make('creator.name')->label(__('workflow.queue.submitted_by'))->placeholder('—'),
                TextColumn::make('submitted_at')->label(__('workflow.queue.submitted_at'))->date()->sortable(),
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
