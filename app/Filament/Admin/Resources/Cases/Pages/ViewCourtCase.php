<?php

namespace App\Filament\Admin\Resources\Cases\Pages;

use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\CourtCase;
use App\Models\Hearing;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

/**
 * The case workspace: Overview · Parties & Charges · Stages timeline · Hearings ·
 * Measures & Sentences · Documents · History.
 */
class ViewCourtCase extends ViewRecord
{
    protected static string $resource = CourtCaseResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('cases.tabs.overview');
    }

    public function getContentTabIcon(): Heroicon
    {
        return Heroicon::OutlinedInformationCircle;
    }

    public function getTitle(): string|Htmlable
    {
        /** @var CourtCase $case */
        $case = $this->getRecord();

        return $case->display_label;
    }

    public function getSubheading(): ?string
    {
        /** @var CourtCase $case */
        $case = $this->getRecord();

        return $case->type->getLabel().' · '.$case->status->getLabel();
    }

    protected function getHeaderActions(): array
    {
        /** @var CourtCase $case */
        $case = $this->getRecord();

        return [
            Action::make('addHearing')
                ->label(__('hearings.actions.add'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->url(HearingResource::getUrl('create', ['case' => $case->getKey()]))
                ->visible(fn (): bool => Gate::allows('create', Hearing::class) && Gate::allows('view', $case)),
            EditAction::make(),
        ];
    }
}
