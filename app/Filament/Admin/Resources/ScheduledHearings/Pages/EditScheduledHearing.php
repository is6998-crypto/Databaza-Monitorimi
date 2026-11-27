<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Pages;

use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingActions;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\ScheduledHearing;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledHearing extends EditRecord
{
    protected static string $resource = ScheduledHearingResource::class;

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof ScheduledHearing
            ? collect([$record->case->display_label, $record->status()->getLabel()])->filter()->implode(' · ')
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            ScheduledHearingActions::attend(),
            ScheduledHearingActions::record(),
            ScheduledHearingActions::openHearing(),
            ScheduledHearingActions::cancel(),
            ScheduledHearingActions::restore(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ScheduledHearingResource::getUrl();
    }
}
