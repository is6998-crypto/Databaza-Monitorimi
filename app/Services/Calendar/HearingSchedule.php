<?php

namespace App\Services\Calendar;

use App\Models\Hearing;
use App\Models\ScheduledHearing;

/**
 * Keeps the hearing calendar in step with the monitored hearings:
 *
 * - the "next hearing" date noted at a hearing becomes a planned hearing (or updates the one it created, or is
 *   added to one already entered by hand for that case and day);
 * - a monitored hearing recorded for a case on a planned day marks that planned hearing as attended.
 */
class HearingSchedule
{
    public function hearingSaved(Hearing $hearing): void
    {
        $this->syncNextHearing($hearing);
        $this->linkAttended($hearing);
    }

    public function hearingDeleted(Hearing $hearing): void
    {
        ScheduledHearing::query()->where('hearing_id', $hearing->id)->get()
            ->each(fn (ScheduledHearing $scheduled) => $scheduled->forceFill(['hearing_id' => null])->save());

        ScheduledHearing::query()->where('source_hearing_id', $hearing->id)->whereNull('hearing_id')->get()
            ->each(fn (ScheduledHearing $scheduled) => $scheduled->delete());
    }

    private function syncNextHearing(Hearing $hearing): void
    {
        $planned = ScheduledHearing::withTrashed()->where('source_hearing_id', $hearing->id)->first();

        // Once monitored, a planned hearing is a record of what happened: later edits of the source leave it alone.
        if ($planned?->hearing_id !== null) {
            return;
        }

        if ($hearing->next_hearing_on === null) {
            if ($planned !== null && ! $planned->trashed()) {
                $planned->delete();
            }

            return;
        }

        if ($planned === null) {
            // Already in the calendar (from the court's schedule, say)? Then that entry is the one.
            $planned = ScheduledHearing::query()->open()
                ->where('case_id', $hearing->case_id)
                ->whereDate('scheduled_on', $hearing->next_hearing_on->toDateString())
                ->whereNull('source_hearing_id')
                ->first()
                ?? new ScheduledHearing(['monitor_id' => $hearing->case->assigned_monitor_id]);

            $planned->source_hearing_id = $hearing->id;
        }

        if ($planned->trashed()) {
            $planned->restore();
        }

        $planned->fill([
            'case_id' => $hearing->case_id,
            'scheduled_on' => $hearing->next_hearing_on->toDateString(),
            'scheduled_time' => $hearing->next_hearing_time ?: $planned->scheduled_time,
        ]);

        if (! $planned->exists || $planned->isDirty()) {
            $planned->save();
        }
    }

    private function linkAttended(Hearing $hearing): void
    {
        // A corrected hearing date no longer matches the planned day it was linked to.
        ScheduledHearing::query()
            ->where('hearing_id', $hearing->id)
            ->where(fn ($query) => $query->where('case_id', '!=', $hearing->case_id)->orWhereDate('scheduled_on', '!=', $hearing->hearing_date->toDateString()))
            ->get()
            ->each(fn (ScheduledHearing $scheduled) => $scheduled->forceFill(['hearing_id' => null])->save());

        ScheduledHearing::query()
            ->open()
            ->where('case_id', $hearing->case_id)
            ->whereDate('scheduled_on', $hearing->hearing_date->toDateString())
            ->where(fn ($query) => $query->whereNull('source_hearing_id')->orWhere('source_hearing_id', '!=', $hearing->id))
            ->get()
            ->each(fn (ScheduledHearing $scheduled) => $scheduled->forceFill(['hearing_id' => $hearing->id])->save());
    }
}
