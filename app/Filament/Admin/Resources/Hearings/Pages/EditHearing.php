<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Concerns\AutosavesDraft;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHearing extends EditRecord
{
    use AutosavesDraft;

    protected static string $resource = HearingResource::class;

    protected function draftKey(): string
    {
        return 'hearing:edit:'.$this->getRecord()->getKey();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $draft = $this->restoreDraft();
        $this->form->fill($draft ?? $this->mutateFormDataBeforeFill($this->getRecord()->attributesToArray()));

        $this->callHook('afterFill');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->discardDraftAction(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->forgetDraft();
    }

    protected function getRedirectUrl(): string
    {
        return HearingResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
