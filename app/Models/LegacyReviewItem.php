<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Legacy values the importer could not map; kept for manual review instead of being dropped.
 *
 * @property int $id
 * @property string $legacy_module
 * @property string $legacy_table
 * @property string $legacy_id
 * @property string $field
 * @property string|null $value
 * @property string $reason
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property string|null $resolution_note
 */
class LegacyReviewItem extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Only the review decision is audited; the raw legacy values never go to the audit log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('legacy')->logOnly(['resolved_at', 'resolution_note'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
