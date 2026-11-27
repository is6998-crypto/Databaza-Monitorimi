<?php

namespace App\Filament\Admin\Resources\ScheduledHearings;

use App\Enums\ScheduledHearingStatus;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * What can be done with a planned hearing, from the calendar, the list and the edit page.
 */
final class ScheduledHearingActions
{
    /**
     * "I will attend": a monitor takes a hearing nobody attends yet.
     */
    public static function attend(): Action
    {
        return Action::make('attend')
            ->label(__('calendar.actions.attend'))
            ->icon(Heroicon::OutlinedHandRaised)
            ->color('primary')
            ->visible(fn (ScheduledHearing $record): bool => Gate::allows('attend', $record))
            ->action(function (ScheduledHearing $record): void {
                Gate::authorize('attend', $record);
                $user = auth()->user();

                if ($user instanceof User && $user->monitor !== null) {
                    $record->update(['monitor_id' => $user->monitor->id]);
                    Notification::make()->title(__('calendar.actions.attend_done'))->success()->send();
                }
            });
    }

    /**
     * Opens "Add hearing" for the case, on the day of the planned hearing (once that day has come).
     */
    public static function record(): Action
    {
        return Action::make('recordHearing')
            ->label(__('calendar.actions.record'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->visible(fn (ScheduledHearing $record): bool => $record->hearing_id === null
                && $record->cancelled_at === null
                && $record->scheduled_on->lte(ScheduledHearing::today())
                && Gate::allows('create', Hearing::class)
                && Gate::allows('view', $record->case))
            ->url(fn (ScheduledHearing $record): string => HearingResource::getUrl('create', ['case' => $record->case_id, 'date' => $record->scheduled_on->toDateString()]));
    }

    /**
     * The monitored hearing of a planned hearing that was attended.
     */
    public static function openHearing(): Action
    {
        return Action::make('openHearing')
            ->label(__('calendar.actions.open_hearing'))
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->visible(fn (ScheduledHearing $record): bool => $record->hearing !== null && Gate::allows('view', $record->hearing))
            ->url(fn (ScheduledHearing $record): ?string => $record->hearing !== null ? HearingResource::getUrl('view', ['record' => $record->hearing]) : null);
    }

    /**
     * The court called the session off, or moved it (then enter the new date as a new planned hearing).
     */
    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label(__('calendar.actions.cancel'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('calendar.actions.cancel_help'))
            ->schema([
                Textarea::make('note')->label(__('calendar.fields.note'))->rows(2)->maxLength(1000),
            ])
            ->visible(fn (ScheduledHearing $record): bool => Gate::allows('cancel', $record))
            ->action(function (ScheduledHearing $record, array $data): void {
                Gate::authorize('cancel', $record);

                $record->forceFill([
                    'cancelled_at' => now(),
                    'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : $record->note,
                ])->save();

                Notification::make()->title(__('calendar.actions.cancel_done'))->success()->send();
            });
    }

    public static function restore(): Action
    {
        return Action::make('reinstate')
            ->label(__('calendar.actions.reinstate'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->visible(fn (ScheduledHearing $record): bool => $record->status() === ScheduledHearingStatus::Cancelled && Gate::allows('update', $record))
            ->action(function (ScheduledHearing $record): void {
                Gate::authorize('update', $record);
                $record->forceFill(['cancelled_at' => null])->save();
            });
    }
}
