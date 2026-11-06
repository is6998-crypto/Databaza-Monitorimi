<?php

namespace App\Filament\Admin\Actions;

use App\Models\Reference\ReferenceModel;
use App\Services\Reference\ReferenceMerger;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Select the duplicates in the table, choose the record to keep, and every reference to
 * the others is re-pointed to it (App\Services\Reference\ReferenceMerger).
 */
class MergeDuplicatesBulkAction
{
    /**
     * @param  class-string<ReferenceModel>  $model
     */
    public static function make(string $model): BulkAction
    {
        return BulkAction::make('mergeDuplicates')
            ->label(__('reference.merge.action'))
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('warning')
            ->modalHeading(__('reference.merge.heading'))
            ->modalDescription(__('reference.merge.description'))
            ->modalSubmitActionLabel(__('reference.merge.submit'))
            ->visible(fn (): bool => Gate::allows('merge', $model))
            ->schema(fn (Collection $records): array => [
                Select::make('keep_id')
                    ->label(__('reference.merge.keep'))
                    ->options($records->mapWithKeys(fn (ReferenceModel $record): array => [$record->getKey() => $record->label().' (#'.$record->getKey().')'])->all())
                    ->default($records->first()?->getKey())
                    ->required(),
            ])
            ->action(function (Collection $records, array $data) use ($model): void {
                Gate::authorize('merge', $model);

                if ($records->count() < 2) {
                    Notification::make()->title(__('reference.merge.need_two'))->warning()->send();

                    return;
                }

                /** @var ReferenceModel $keep */
                $keep = $records->first(fn (ReferenceModel $record): bool => (string) $record->getKey() === (string) $data['keep_id']);
                $stats = app(ReferenceMerger::class)->merge($keep, $records->all());

                Notification::make()
                    ->title(__('reference.merge.done', ['count' => $records->count() - 1, 'name' => $keep->label()]))
                    ->body(__('reference.merge.repointed', ['count' => array_sum($stats)]))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
