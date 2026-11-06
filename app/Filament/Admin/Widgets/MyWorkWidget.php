<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dashboard for monitors: their hearings that are drafts or were returned for correction.
 */
class MyWorkWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole('monitor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.my_work.title'))
            ->description(__('dashboard.my_work.description'))
            ->query(fn (): Builder => Hearing::query()
                ->where('created_by', auth()->id())
                ->whereIn('status', [WorkflowStatus::Draft->value, WorkflowStatus::Returned->value])
                ->with('case.court'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date(),
                TextColumn::make('case_id')->label(__('hearings.fields.case'))->formatStateUsing(fn (Hearing $record): string => $record->case->display_label)->wrap(),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge(),
                TextColumn::make('return_comment')->label(__('workflow.comment'))->placeholder('—')->wrap()->limit(80),
            ])
            ->recordUrl(fn (Hearing $record): string => HearingResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('dashboard.my_work.empty'))
            ->paginated([5, 10]);
    }
}
