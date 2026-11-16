<?php

namespace App\Filament\Admin\Resources\HearingReports\Pages;

use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Models\HearingReport;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHearingReport extends EditRecord
{
    protected static string $resource = HearingReportResource::class;

    public function getSubheading(): ?string
    {
        /** @var HearingReport $report */
        $report = $this->getRecord();

        return HearingReportResource::hearingLabel($report->hearing);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return HearingReportResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
