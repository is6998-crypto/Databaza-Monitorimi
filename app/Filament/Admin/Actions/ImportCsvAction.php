<?php

namespace App\Filament\Admin\Actions;

use App\Models\Reference\ReferenceModel;
use App\Services\Reference\CsvImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk CSV import for a reference table, plus a matching template download.
 */
class ImportCsvAction
{
    /**
     * @param  class-string<ReferenceModel>  $model
     */
    public static function make(string $model, CsvImporter $importer): Action
    {
        return Action::make('importCsv')
            ->label(__('reference.import.action'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->visible(fn (): bool => Gate::allows('import', $model))
            ->modalDescription(__('reference.import.description', ['columns' => implode(', ', $importer->header())]))
            ->schema([
                FileUpload::make('file')
                    ->label(__('reference.import.file'))
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->maxSize(5120)
                    ->required(),
            ])
            ->action(function (array $data) use ($model, $importer): void {
                Gate::authorize('import', $model);

                $path = Storage::disk('local')->path((string) $data['file']);

                try {
                    $result = $importer->import($path);
                } catch (RuntimeException $exception) {
                    Notification::make()->title(__('reference.import.failed'))->body($exception->getMessage())->danger()->send();

                    return;
                } finally {
                    Storage::disk('local')->delete((string) $data['file']);
                }

                $notification = Notification::make()
                    ->title(__('reference.import.done', ['created' => $result['created'], 'updated' => $result['updated'], 'skipped' => $result['skipped']]))
                    ->body(implode("\n", array_slice($result['errors'], 0, 10)));

                ($result['errors'] === [] ? $notification->success() : $notification->warning())->persistent()->send();
            });
    }

    public static function template(string $filename, CsvImporter $importer): Action
    {
        return Action::make('csvTemplate')
            ->label(__('reference.import.template'))
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->action(fn (): StreamedResponse => response()->streamDownload(function () use ($importer): void {
                echo implode(',', $importer->header())."\n";
            }, $filename.'-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']));
    }
}
