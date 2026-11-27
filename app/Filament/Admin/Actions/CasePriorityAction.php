<?php

namespace App\Filament\Admin\Actions;

use App\Enums\CasePriority;
use App\Models\CourtCase;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Setting a case's priority: from the case page (one case) and from the case list (the selected cases).
 * Only for users with Prioritize:Case (editors); it works whatever the workflow status, published cases included,
 * because priority is planning, not monitoring data. The change is audited with the case.
 */
final class CasePriorityAction
{
    public static function make(): Action
    {
        return Action::make('setPriority')
            ->label(__('cases.priority.action'))
            ->icon(Heroicon::OutlinedFlag)
            ->color('gray')
            ->modalHeading(__('cases.priority.action'))
            ->modalDescription(__('cases.help.priority'))
            ->fillForm(fn (CourtCase $record): array => ['priority' => $record->priority->value, 'priority_note' => $record->priority_note])
            ->schema(self::fields())
            ->visible(fn (): bool => Gate::allows('prioritize', CourtCase::class))
            ->action(function (CourtCase $record, array $data): void {
                Gate::authorize('prioritize', CourtCase::class);
                self::apply($record, $data);

                Notification::make()->title(trans_choice('cases.priority.done', 1))->success()->send();
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('setPriority')
            ->label(__('cases.priority.action'))
            ->icon(Heroicon::OutlinedFlag)
            ->modalDescription(__('cases.help.priority'))
            ->schema(self::fields())
            ->visible(fn (): bool => Gate::allows('prioritize', CourtCase::class))
            ->action(function (Collection $records, array $data): void {
                Gate::authorize('prioritize', CourtCase::class);
                $updated = 0;

                foreach ($records as $case) {
                    if ($case instanceof CourtCase && Gate::allows('view', $case)) {
                        self::apply($case, $data);
                        $updated++;
                    }
                }

                Notification::make()->title(trans_choice('cases.priority.done', $updated, ['count' => $updated]))->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return list<ToggleButtons|Textarea>
     */
    private static function fields(): array
    {
        return [
            ToggleButtons::make('priority')
                ->label(__('cases.fields.priority'))
                ->options(CasePriority::options())
                ->colors([CasePriority::High->value => 'primary', CasePriority::Normal->value => 'gray', CasePriority::Low->value => 'gray'])
                ->icons([CasePriority::High->value => CasePriority::High->getIcon(), CasePriority::Normal->value => CasePriority::Normal->getIcon(), CasePriority::Low->value => CasePriority::Low->getIcon()])
                ->default(CasePriority::Normal->value)
                ->inline()
                ->grouped()
                ->required(),
            Textarea::make('priority_note')
                ->label(__('cases.fields.priority_note'))
                ->placeholder(__('cases.help.priority_note'))
                ->rows(3)
                ->maxLength(500),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function apply(CourtCase $case, array $data): void
    {
        $priority = CasePriority::from((string) $data['priority']);

        // A normal case keeps no reason.
        $case->forceFill([
            'priority' => $priority,
            'priority_note' => $priority === CasePriority::Normal ? null : (filled($data['priority_note'] ?? null) ? trim((string) $data['priority_note']) : null),
        ])->save();
    }
}
