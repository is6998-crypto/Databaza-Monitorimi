<?php

namespace App\Models;

use App\Enums\ResearcherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $institution
 * @property string|null $position
 * @property string $research_purpose
 * @property ResearcherStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property string|null $agreement_version
 * @property Carbon|null $agreement_accepted_at
 * @property-read User $user
 */
class ResearcherProfile extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ResearcherStatus::class,
            'reviewed_at' => 'datetime',
            'agreement_accepted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('researchers')->logOnly(['status', 'review_note', 'agreement_version', 'agreement_accepted_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function hasAcceptedCurrentAgreement(): bool
    {
        return $this->agreement_accepted_at !== null
            && $this->agreement_version === config('courtwatch.data_use_agreement_version');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
