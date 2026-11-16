<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Actions\WorkflowActions;
use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\Hearing;
use App\Models\HearingReport;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ViewHearing extends ViewRecord
{
    protected static string $resource = HearingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The written account of this session: open it if it exists, otherwise start writing it.
            Action::make('hearingReport')
                ->label(fn (Hearing $record): string => $record->report !== null ? __('reports.actions.open_for_hearing') : __('reports.actions.write_for_hearing'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (Hearing $record): string => $record->report !== null
                    ? HearingReportResource::getUrl('view', ['record' => $record->report])
                    : HearingReportResource::getUrl('create', ['hearing' => $record->getKey()]))
                ->visible(fn (Hearing $record): bool => $record->report !== null
                    ? Gate::allows('view', $record->report)
                    : Gate::allows('create', HearingReport::class) && HearingReportResource::reportableHearings()->whereKey($record->getKey())->exists()),
            EditAction::make(),
            ...WorkflowActions::all(),
        ];
    }
}
