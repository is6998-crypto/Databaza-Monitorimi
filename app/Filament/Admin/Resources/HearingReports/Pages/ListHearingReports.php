<?php

namespace App\Filament\Admin\Resources\HearingReports\Pages;

use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHearingReports extends ListRecords
{
    protected static string $resource = HearingReportResource::class;

    public function getSubheading(): ?string
    {
        return __('reports.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('reports.actions.write')),
        ];
    }
}
