<?php

namespace App\Filament\Admin\Resources\Hearings\Pages;

use App\Filament\Admin\Concerns\AutosavesDraft;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Filament\Admin\Resources\Hearings\Schemas\HearingForm;
use App\Models\CourtCase;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

/**
 * "Add hearing": opened from a case (?case=ID) the court, judge, prosecutor and parties are
 * pre-filled and only hearing-specific questions remain. The form autosaves as a draft.
 */
class CreateHearing extends CreateRecord
{
    use AutosavesDraft;

    protected static string $resource = HearingResource::class;

    public ?int $caseId = null;

    public function mount(): void
    {
        $case = request()->integer('case') ?: null;

        if ($case !== null) {
            $model = CourtCase::query()->findOrFail($case);
            Gate::authorize('view', $model);
            $this->caseId = $model->id;
        }

        parent::mount();
    }

    protected function draftKey(): string
    {
        return 'hearing:create:'.($this->caseId ?? 'any');
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $user = auth()->user();
        $draft = $this->restoreDraft();
        $case = $this->caseId ? CourtCase::query()->find($this->caseId) : null;

        $defaults = HearingForm::defaultsFor($case, $user instanceof User ? $user : null);

        // Opened from the hearing calendar: the day of the planned hearing (never a day still to come).
        $date = request()->string('date')->toString();
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $date <= $defaults['hearing_date']) {
            $defaults['hearing_date'] = $date;
        }

        $this->form->fill($draft ?? $defaults);

        $this->callHook('afterFill');
    }

    public function getSubheading(): ?string
    {
        return $this->caseId ? CourtCase::query()->with('court')->find($this->caseId)?->display_label : null;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isMonitorOnly()) {
            $data['monitor_id'] = $user->monitor?->id;
        }

        $case = CourtCase::query()->findOrFail($data['case_id']);
        Gate::authorize('view', $case);

        return $data;
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
        return HearingResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
