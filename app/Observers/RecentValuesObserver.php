<?php

namespace App\Observers;

use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Measure;
use App\Models\User;
use App\Services\RecentValues;
use Illuminate\Database\Eloquent\Model;

/**
 * Remembers which reference values the current user picked when saving records.
 */
class RecentValuesObserver
{
    /** @var array<class-string<Model>, list<string>> */
    public const FIELDS = [
        CourtCase::class => ['court_id', 'court_branch_id', 'department_id', 'division_id', 'judge_id', 'prosecutor_id', 'prosecution_office_id'],
        Hearing::class => ['court_id', 'judge_id', 'prosecutor_id'],
        Charge::class => ['criminal_offence_id'],
        CaseParty::class => ['nationality_id', 'economic_status_id', 'defendant_position_id'],
        CaseStage::class => ['applicant_id', 'prosecutor_id'],
        Measure::class => ['security_measure_type_id'],
    ];

    public static function register(): void
    {
        foreach (array_keys(self::FIELDS) as $model) {
            $model::saved(fn (Model $record) => (new self)->saved($record));
        }
    }

    public function saved(Model $record): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        foreach (self::FIELDS[$record::class] ?? [] as $field) {
            if ($record->wasChanged($field) || $record->wasRecentlyCreated) {
                $value = $record->getAttribute($field);
                RecentValues::remember($user, $field, $value === null ? null : (int) $value);
            }
        }
    }
}
