<?php

namespace App\Models;

use App\Enums\CounselType;
use App\Enums\Gender;
use App\Enums\PartyRole;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use App\Support\BlindIndex;
use Database\Factories\CasePartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A party to a case. The name is encrypted at rest and never leaves the staff panel;
 * the blind index columns allow exact-token search without storing plaintext.
 *
 * @property int $id
 * @property int $case_id
 * @property PartyRole $role
 * @property string|null $name
 * @property string|null $name_index
 * @property string|null $name_tokens
 * @property Gender|null $gender
 * @property int|null $nationality_id
 * @property int|null $economic_status_id
 * @property int|null $defendant_position_id
 * @property CounselType|null $counsel_type
 * @property bool|null $counsel_at_police
 * @property-read CourtCase $case
 * @property-read Nationality|null $nationality
 * @property-read EconomicStatus|null $economicStatus
 * @property-read DefendantPosition|null $defendantPosition
 */
class CaseParty extends Model
{
    /** @use HasFactory<CasePartyFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'name_index', 'name_tokens', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['name', 'name_index', 'name_tokens'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'role' => PartyRole::class,
            'gender' => Gender::class,
            'counsel_type' => CounselType::class,
            'counsel_at_police' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $party): void {
            if ($party->isDirty('name')) {
                $party->name_index = BlindIndex::fullName($party->name);
                $party->name_tokens = BlindIndex::tokenString($party->name);
            }
        });
    }

    protected static function newFactory(): CasePartyFactory
    {
        return CasePartyFactory::new();
    }

    /**
     * Names are never written to the audit log in clear text; only the fact that they changed.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cases')
            ->logAll()
            ->logExcept(['name', 'name_index', 'name_tokens', 'created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($activity instanceof ActivityModel && ($this->wasChanged('name') || $eventName === 'created')) {
            $activity->properties = $activity->properties->put('name_changed', true);
        }
    }

    /**
     * Internal label: "Accused: N. Surname". Staff panel only.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->role->getLabel().': '.($this->name ?: '—');
    }

    /**
     * @return BelongsTo<CourtCase, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    /**
     * @return BelongsTo<Nationality, $this>
     */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * @return BelongsTo<EconomicStatus, $this>
     */
    public function economicStatus(): BelongsTo
    {
        return $this->belongsTo(EconomicStatus::class);
    }

    /**
     * Position held by the accused (e.g. public official).
     *
     * @return BelongsTo<DefendantPosition, $this>
     */
    public function defendantPosition(): BelongsTo
    {
        return $this->belongsTo(DefendantPosition::class);
    }

    /**
     * @return HasMany<Charge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * @return HasMany<Sentence, $this>
     */
    public function sentences(): HasMany
    {
        return $this->hasMany(Sentence::class);
    }

    /**
     * @return BelongsToMany<Hearing, $this>
     */
    public function hearings(): BelongsToMany
    {
        return $this->belongsToMany(Hearing::class, 'hearing_party');
    }
}
