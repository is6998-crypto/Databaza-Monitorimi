<?php

namespace App\Filament\Admin\Resources\HearingReports\Pages;

use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Models\Hearing;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

/**
 * "Write report": opened from a hearing (?hearing=ID) the hearing is already chosen. If that hearing has a report
 * already, the person is taken to it instead.
 */
class CreateHearingReport extends CreateRecord
{
    protected static string $resource = HearingReportResource::class;

    public ?int $hearingId = null;

    public function mount(): void
    {
        $id = request()->integer('hearing') ?: null;

        if ($id !== null) {
            $hearing = Hearing::query()->with('report')->findOrFail($id);
            Gate::authorize('view', $hearing);

            if ($hearing->report !== null) {
                $this->redirect(HearingReportResource::getUrl('view', ['record' => $hearing->report]));

                return;
            }

            $this->hearingId = $hearing->id;
        }

        parent::mount();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');
        $this->form->fill(['hearing_id' => $this->hearingId]);
        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The hearing must be one this person may write about (the options are filtered, the server checks again).
        abort_unless(HearingReportResource::reportableHearings()->whereKey($data['hearing_id'])->exists(), 403);

        return $data;
    }

    public function getTitle(): string
    {
        return __('reports.actions.write');
    }

    protected function getRedirectUrl(): string
    {
        return HearingReportResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
