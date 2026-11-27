<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Tables;

use App\Enums\CasePriority;
use App\Enums\ScheduledHearingStatus;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingActions;
use App\Filament\Admin\Support\CaseColumn;
use App\Models\CourtCase;
use App\Models\Reference\Court;
use App\Models\Reference\Monitor;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduledHearingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['case.court', 'monitor.user', 'hearing']))
            ->defaultSort(function (Builder $query): Builder {
                ScheduledHearing::sortChronologically($query);

                return $query;
            })
            ->columns([
                TextColumn::make('scheduled_on')
                    ->label(__('calendar.fields.when'))
                    ->state(fn (ScheduledHearing $record): string => $record->whenLabel())
                    ->description(fn (ScheduledHearing $record): ?string => $record->courtroom)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('scheduled_on', $direction)->orderBy('scheduled_time', $direction)),
                CaseColumn::make('case_id', fn (Model $record): ?CourtCase => $record instanceof ScheduledHearing ? $record->case : null),
                TextColumn::make('case_priority')->label(__('cases.fields.priority'))
                    ->state(fn (ScheduledHearing $record): ?CasePriority => $record->case->priority === CasePriority::Normal ? null : $record->case->priority)
                    ->badge(),
                TextColumn::make('monitor.user.name')
                    ->label(__('calendar.fields.monitor'))
                    ->placeholder(__('calendar.nobody')),
                TextColumn::make('status')
                    ->label(__('cases.fields.status'))
                    ->state(fn (ScheduledHearing $record): ScheduledHearingStatus => $record->status())
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('court')
                    ->label(__('cases.fields.court'))
                    ->options(fn (): array => Court::query()->where('is_active', true)->orderBy('name_sq')->get()->mapWithKeys(fn (Court $court): array => [$court->id => $court->label()])->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('case', fn (Builder $case) => $case->where('court_id', $data['value']))
                        : $query),
                SelectFilter::make('monitor_id')
                    ->label(__('calendar.fields.monitor'))
                    ->options(fn (): array => Monitor::selectOptions()),
                Filter::make('mine')
                    ->label(__('calendar.filters.mine'))
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        $user = auth()->user();

                        return $query->where('monitor_id', $user instanceof User ? $user->monitor?->id : null);
                    }),
                Filter::make('nobody')
                    ->label(__('calendar.filters.nobody'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('monitor_id')),
                Filter::make('high_priority')
                    ->label(__('calendar.filters.high_priority'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereHas('case', fn (Builder $case) => $case->where('priority', CasePriority::High->value))),
            ])
            ->recordActions([
                ScheduledHearingActions::attend(),
                ScheduledHearingActions::record(),
                ActionGroup::make([
                    EditAction::make(),
                    ScheduledHearingActions::openHearing(),
                    ScheduledHearingActions::cancel(),
                    ScheduledHearingActions::restore(),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('calendar.empty'));
    }
}
