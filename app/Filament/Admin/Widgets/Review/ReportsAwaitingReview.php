<?php

namespace App\Filament\Admin\Widgets\Review;

use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CourtCase;
use App\Models\HearingReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;

/**
 * Editor review queue — hearing reports the monitors sent in, oldest first.
 */
class ReportsAwaitingReview extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('reports.queue.title'))
            ->query(fn () => HearingReport::query()->awaitingReview()->with(['hearing.case.court', 'author'])->withCount('comments'))
            ->defaultSort('submitted_at')
            ->columns([
                TextColumn::make('hearing.hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                CaseColumn::make('hearing_id', fn (Model $record): ?CourtCase => $record instanceof HearingReport ? $record->hearing->case : null),
                TextColumn::make('title')->label(__('reports.fields.title'))->placeholder('—')->wrap()->limit(60),
                TextColumn::make('author.name')->label(__('reports.fields.author'))->placeholder('—'),
                TextColumn::make('submitted_at')->label(__('workflow.queue.submitted_at'))->date()->sortable(),
                TextColumn::make('comments_count')->label(__('reports.fields.comments'))->numeric()->alignEnd(),
            ])
            ->recordUrl(fn (HearingReport $record): string => HearingReportResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('reports.queue.empty'));
    }
}
