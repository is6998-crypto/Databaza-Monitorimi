<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
