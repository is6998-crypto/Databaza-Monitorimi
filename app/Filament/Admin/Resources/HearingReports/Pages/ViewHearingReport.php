<?php

namespace App\Filament\Admin\Resources\HearingReports\Pages;

use App\Enums\ReportStatus;
use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Models\HearingReport;
use App\Models\User;
use App\Services\Documents\HearingReportDocument;
use App\Services\HearingReportService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ViewHearingReport extends ViewRecord
{
    protected static string $resource = HearingReportResource::class;

    public function getTitle(): string
    {
        return __('reports.model');
    }

    public function getSubheading(): ?string
    {
        /** @var HearingReport $report */
        $report = $this->getRecord();

        return HearingReportResource::hearingLabel($report->hearing);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('download')
                ->label(__('documents.download'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading(__('documents.download'))
                ->modalDescription(__('documents.download_help'))
                ->modalSubmitActionLabel(__('documents.download'))
                ->schema([
                    ToggleButtons::make('format')
                        ->label(__('documents.format'))
                        ->options((array) __('documents.formats'))
                        ->default('docx')
                        ->inline()
                        ->grouped()
                        ->required(),
                    Toggle::make('comments')->label(__('documents.with_comments')),
                ])
                ->visible(fn (HearingReport $record): bool => Gate::allows('export', $record))
                ->action(function (HearingReport $record, array $data, HearingReportDocument $documents) {
                    Gate::authorize('export', $record);
                    $user = auth()->user();

                    return $documents->download($record, $data['format'] === 'pdf' ? 'pdf' : 'docx', (bool) ($data['comments'] ?? false), $user instanceof User ? $user : null);
                }),

            Action::make('submitReport')
                ->label(__('reports.actions.submit'))
                ->icon(ReportStatus::Submitted->getIcon())
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription(__('reports.actions.submit_help'))
                ->visible(fn (HearingReport $record): bool => Gate::allows('submit', $record))
                ->action($this->run(fn (HearingReportService $service, HearingReport $record, User $user) => $service->submit($record, $user), 'reports.done.submitted')),

            Action::make('approveReport')
                ->label(__('reports.actions.approve'))
                ->icon(ReportStatus::Approved->getIcon())
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(__('reports.actions.approve_help'))
                ->visible(fn (HearingReport $record): bool => Gate::allows('approve', $record))
                ->action($this->run(fn (HearingReportService $service, HearingReport $record, User $user) => $service->approve($record, $user), 'reports.done.approved')),

            Action::make('sendBackReport')
                ->label(__('reports.actions.send_back'))
                ->icon(ReportStatus::Returned->getIcon())
                ->color('gray')
                ->modalHeading(__('reports.actions.send_back'))
                ->modalDescription(__('reports.actions.send_back_help'))
                ->schema([
                    Textarea::make('comment')->label(__('reports.comments.field'))->required()->rows(4)->maxLength(5000),
                ])
                ->visible(fn (HearingReport $record): bool => Gate::allows('sendBack', $record))
                ->action(function (HearingReport $record, array $data, Action $action): void {
                    $this->run(fn (HearingReportService $service, HearingReport $record, User $user) => $service->sendBack($record, $user, (string) $data['comment']), 'reports.done.returned')($record, $action);
                }),
        ];
    }

    /**
     * @param  Closure(HearingReportService, HearingReport, User): void  $operation
     * @return Closure(HearingReport, Action): void
     */
    private function run(Closure $operation, string $successKey): Closure
    {
        return function (HearingReport $record, Action $action) use ($operation, $successKey): void {
            $user = auth()->user();

            if (! $user instanceof User) {
                abort(403);
            }

            try {
                $operation(app(HearingReportService::class), $record, $user);
            } catch (ValidationException $exception) {
                Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                $action->halt();
            }

            Notification::make()->title(__($successKey))->success()->send();

            // The status, the buttons and the comment thread all change.
            $this->redirect(HearingReportResource::getUrl('view', ['record' => $record]));
        };
    }
}
