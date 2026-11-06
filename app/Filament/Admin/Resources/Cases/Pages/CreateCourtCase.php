<?php

namespace App\Filament\Admin\Resources\Cases\Pages;

use App\Filament\Admin\Concerns\AutosavesDraft;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourtCase extends CreateRecord
{
    use AutosavesDraft;

    protected static string $resource = CourtCaseResource::class;

    protected function draftKey(): string
    {
        return 'case:create';
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $draft = $this->restoreDraft();

        $draft !== null ? $this->form->fill($draft) : $this->form->fill();

        $this->callHook('afterFill');
    }

    protected function getHeaderActions(): array
    {
        return [$this->discardDraftAction()];
    }

    protected function afterCreate(): void
    {
        $this->forgetDraft();
    }

    protected function getRedirectUrl(): string
    {
        return CourtCaseResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
