<?php

namespace App\Filament\Admin\Actions;

use App\Enums\ExportFormat;
use App\Enums\WorkflowStatus;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\Reference\Monitor;
use App\Models\User;
use App\Services\Export\ExportService;
use App\Services\WorkflowService;
use App\Support\Permissions;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export, bulk-assign and bulk status actions for the case and hearing lists (proposal B6.6).
 */
final class TableActions
{
    /**
     * Exports everything matching the current filters and search.
     *
     * @param  Closure(): array<string, Closure>  $columns
     * @param  list<string>  $relations
     */
    public static function exportFiltered(string $resource, Closure $columns, array $relations): Action
    {
        return Action::make('exportFiltered')
            ->label(__('exports.actions.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (): bool => self::canExport())
            ->modalDescription(__('exports.actions.export_help'))
            ->schema([self::formatField()])
            ->action(function (array $data, ListRecords $livewire) use ($resource, $columns, $relations): StreamedResponse {
                abort_unless(self::canExport(), 403);

                /** @var Builder<Model> $query */
                $query = $livewire->getFilteredSortedTableQuery();

                return app(ExportService::class)->stream(
                    $resource,
                    $query->with($relations),
                    $columns(),
                    ExportFormat::from($data['format']),
                    ExportLog::CONTEXT_ADMIN,
                    self::user(),
                    ['filters' => $livewire->tableFilters, 'search' => $livewire->tableSearch],
                );
            });
    }

    /**
     * Exports the selected rows.
     *
     * @param  Closure(): array<string, Closure>  $columns
     * @param  list<string>  $relations
     */
    public static function exportSelected(string $resource, Closure $columns, array $relations): BulkAction
    {
        return BulkAction::make('exportSelected')
            ->label(__('exports.actions.export_selected'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (): bool => self::canExport())
            ->schema([self::formatField()])
            ->action(function (Collection $records, array $data) use ($resource, $columns, $relations): StreamedResponse {
                abort_unless(self::canExport(), 403);

                $model = $records->first();
                $query = $model::query()->whereKey($records->modelKeys())->with($relations);

                return app(ExportService::class)->stream(
                    $resource,
                    $query,
                    $columns(),
                    ExportFormat::from($data['format']),
                    ExportLog::CONTEXT_ADMIN,
                    self::user(),
                    ['selected' => count($records)],
                );
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function assignMonitor(): BulkAction
    {
        return BulkAction::make('assignMonitor')
            ->label(__('exports.actions.assign_monitor'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(fn (): bool => Gate::allows('assignMonitor', CourtCase::class))
            ->schema([
                Select::make('monitor_id')
                    ->label(__('cases.fields.assigned_monitor'))
                    ->options(fn (): array => Monitor::selectOptions())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                Gate::authorize('assignMonitor', CourtCase::class);
                $updated = 0;

                foreach ($records as $case) {
                    if ($case instanceof CourtCase && Gate::allows('view', $case)) {
                        $case->update(['assigned_monitor_id' => (int) $data['monitor_id']]);
                        $updated++;
                    }
                }

                Notification::make()->title(__('exports.done.assigned', ['count' => $updated]))->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Moves the selected records through the review workflow (each one is checked separately).
     */
    public static function changeStatus(): BulkAction
    {
        return BulkAction::make('changeStatus')
            ->label(__('exports.actions.change_status'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (): bool => self::user()?->checkPermissionTo(Permissions::REVIEW) ?? false)
            ->schema([
                Select::make('status')
                    ->label(__('cases.fields.status'))
                    ->options([
                        WorkflowStatus::InReview->value => WorkflowStatus::InReview->getLabel(),
                        WorkflowStatus::Approved->value => WorkflowStatus::Approved->getLabel(),
                        WorkflowStatus::Published->value => WorkflowStatus::Published->getLabel(),
                        WorkflowStatus::Returned->value => WorkflowStatus::Returned->getLabel(),
                    ])
                    ->required()
                    ->live(),
                Textarea::make('comment')
                    ->label(__('workflow.comment'))
                    ->required(fn (Get $get): bool => $get('status') === WorkflowStatus::Returned->value)
                    ->visible(fn (Get $get): bool => in_array($get('status'), [WorkflowStatus::Returned->value, WorkflowStatus::Approved->value], true)),
            ])
            ->action(function (Collection $records, array $data): void {
                $user = self::user();
                abort_unless($user !== null, 403);

                $to = WorkflowStatus::from($data['status']);
                $done = 0;
                $skipped = 0;

                foreach ($records as $record) {
                    if (! $record instanceof CourtCase && ! $record instanceof Hearing) {
                        continue;
                    }

                    try {
                        app(WorkflowService::class)->transition($record, $to, $user, $data['comment'] ?? null);
                        $done++;
                    } catch (ValidationException|AuthorizationException) {
                        $skipped++;
                    }
                }

                Notification::make()
                    ->title(__('exports.done.status', ['done' => $done, 'skipped' => $skipped]))
                    ->{$skipped > 0 ? 'warning' : 'success'}()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function formatField(): Select
    {
        return Select::make('format')
            ->label(__('exports.fields.format'))
            ->options(ExportFormat::options())
            ->default(ExportFormat::Xlsx->value)
            ->required();
    }

    private static function canExport(): bool
    {
        return self::user()?->checkPermissionTo(Permissions::EXPORT_DATA) ?? false;
    }

    private static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
