<?php

namespace App\Services;

use App\Enums\StageType;
use App\Models\CaseStage;
use App\Models\CourtCase;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Stage dates must be chronological (proposal B5): a stage cannot be dated before any of its
 * predecessors, nor after any stage that must follow it.
 */
class StageChronology
{
    /**
     * @return string|null translated error, or null when the date fits the timeline
     */
    public static function check(CourtCase $case, StageType $stage, CarbonInterface|string|null $date, ?int $ignoreStageId = null): ?string
    {
        if (blank($date)) {
            return null;
        }

        $date = Carbon::parse($date)->startOfDay();
        $predecessors = $stage->allPredecessors();

        $others = $case->stages()
            ->whereNotNull('date')
            ->when($ignoreStageId, fn ($query) => $query->whereKeyNot($ignoreStageId))
            ->where('stage', '!=', $stage->value)
            ->get();

        foreach ($others as $other) {
            /** @var CaseStage $other */
            $otherDate = $other->date;

            if ($otherDate === null) {
                continue;
            }

            if (in_array($other->stage, $predecessors, true) && $otherDate->gt($date)) {
                return self::message($stage, $date, $other->stage, $otherDate);
            }

            if (in_array($stage, $other->stage->allPredecessors(), true) && $otherDate->lt($date)) {
                return self::message($other->stage, $otherDate, $stage, $date);
            }
        }

        return null;
    }

    /**
     * Checks a full set of stage dates (used by the importer and the API).
     *
     * @param  array<string, string|null>  $dates  stage value => Y-m-d
     * @return list<string>
     */
    public static function checkSet(array $dates): array
    {
        $errors = [];

        foreach ($dates as $value => $date) {
            $stage = StageType::tryFrom($value);

            if ($stage === null || blank($date)) {
                continue;
            }

            foreach ($stage->allPredecessors() as $predecessor) {
                $previous = $dates[$predecessor->value] ?? null;

                if (filled($previous) && Carbon::parse($previous)->gt(Carbon::parse($date))) {
                    $errors[] = self::message($stage, Carbon::parse($date), $predecessor, Carbon::parse($previous));
                }
            }
        }

        return $errors;
    }

    private static function message(StageType $stage, CarbonInterface $date, StageType $previous, CarbonInterface $previousDate): string
    {
        $format = (string) config('courtwatch.date_format');

        $message = __('validation.stage_chronology', [
            'stage' => $stage->getLabel(),
            'date' => $date->format($format),
            'previous' => $previous->getLabel(),
            'previous_date' => $previousDate->format($format),
        ]);

        return is_string($message) ? $message : 'Stage dates are not chronological.';
    }
}
