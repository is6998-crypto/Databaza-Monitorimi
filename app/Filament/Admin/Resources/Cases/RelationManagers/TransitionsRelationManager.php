<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Models\WorkflowTransition;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Review workflow history (submitted, returned with comment, approved, published…).
 */
class TransitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transitions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('workflow.history');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('audit.fields.created_at'))->dateTime(),
                TextColumn::make('from_status')->label(__('workflow.from'))->badge()->placeholder('—'),
                TextColumn::make('to_status')->label(__('workflow.to'))->badge()->icon(Heroicon::OutlinedArrowRight),
                TextColumn::make('user.name')->label(__('audit.fields.causer'))->placeholder(__('audit.system')),
                TextColumn::make('comment')->label(__('workflow.comment'))->wrap()->placeholder('—'),
            ])
            ->recordAction(null)
            ->emptyStateHeading(__('workflow.no_history'))
            ->paginated([10, 25])
            ->recordTitle(fn (WorkflowTransition $record): string => $record->to_status->getLabel());
    }
}
