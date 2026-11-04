<?php

namespace App\Filament\Admin\Resources\Cases\Pages;

use App\Filament\Admin\Concerns\AutosavesDraft;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCourtCase extends EditRecord
{
    use AutosavesDraft;

    protected static string $resource = CourtCaseResource::class;

    protected function draftKey(): string
    {
        return 'case:edit:'.$this->getRecord()->getKey();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $draft = $this->restoreDraft();
        $data = $draft ?? $this->mutateFormDataBeforeFill($this->getRecord()->attributesToArray());

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->discardDraftAction(),
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->forgetDraft();
    }

    protected function getRedirectUrl(): string
    {
        return CourtCaseResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
