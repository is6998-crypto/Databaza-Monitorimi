<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Pages;

use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Filament\Admin\Resources\ScheduledHearings\Schemas\ScheduledHearingForm;
use App\Models\CourtCase;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

/**
 * Add a hearing to the calendar (from the court's schedule, say). Opened from a case (?case=ID) or a day of the
 * month view (?date=Y-m-d), those are filled in.
 */
class CreateScheduledHearing extends CreateRecord
{
    protected static string $resource = ScheduledHearingResource::class;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $case = request()->integer('case') ?: null;
        $date = request()->string('date')->toString();

        $this->form->fill([
            'case_id' => $case !== null && ($model = CourtCase::query()->find($case)) !== null && Gate::allows('view', $model) ? $model->id : null,
            'scheduled_on' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $date >= ScheduledHearing::today()->toDateString() ? $date : null,
        ]);

        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $case = CourtCase::query()->findOrFail($data['case_id']);
        Gate::authorize('view', $case);

        // Without the right to choose, the monitor of the case attends, or whoever entered the hearing.
        if (! ScheduledHearingForm::canAssign(null)) {
            $user = auth()->user();
            $data['monitor_id'] = $case->assigned_monitor_id ?? ($user instanceof User ? $user->monitor?->id : null);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ScheduledHearingResource::getUrl();
    }
}
