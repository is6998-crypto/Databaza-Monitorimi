<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Hearings of the case. Recording a hearing opens the hearing form pre-filled from the case.
 */
class HearingsRelationManager extends RelationManager
{
    protected static string $relationship = 'hearings';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedCalendarDays;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('hearings.plural');
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['monitor.user'])->orderByDesc('hearing_date'))
            ->recordUrl(fn (Hearing $record): ?string => Gate::allows('view', $record) ? HearingResource::getUrl('view', ['record' => $record]) : null)
            ->columns([
                TextColumn::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                TextColumn::make('hearing_type')->label(__('hearings.fields.hearing_type'))->badge()->placeholder('—'),
                TextColumn::make('monitor.user.name')->label(__('hearings.fields.monitor'))->placeholder('—')->toggleable(),
                IconColumn::make('was_postponed')->label(__('hearings.fields.was_postponed'))->boolean()->trueColor('warning')->falseColor('success'),
                IconColumn::make('started_late')->label(__('hearings.fields.started_late'))->boolean()->trueColor('warning')->falseColor('success')->toggleable(),
                IconColumn::make('is_public')->label(__('hearings.fields.is_public'))->boolean()->toggleable(),
                TextColumn::make('status')->label(__('cases.fields.status'))->badge(),
            ])
            ->headerActions([
                Action::make('addHearing')
                    ->label(__('hearings.actions.add'))
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->url(fn (): string => HearingResource::getUrl('create', ['case' => $this->getOwnerRecord()->getKey()]))
                    ->visible(fn (): bool => Gate::allows('create', Hearing::class)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('cases.actions.open'))
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Hearing $record): string => HearingResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (Hearing $record): bool => Gate::allows('view', $record)),
                Action::make('edit')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Hearing $record): string => HearingResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (Hearing $record): bool => Gate::allows('update', $record)),
            ])
            ->emptyStateHeading(__('hearings.empty'));
    }
}
