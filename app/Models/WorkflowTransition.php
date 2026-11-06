<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property WorkflowStatus|null $from_status
 * @property WorkflowStatus $to_status
 * @property int|null $user_id
 * @property string|null $comment
 * @property array<string, mixed>|null $snapshot
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class WorkflowTransition extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => WorkflowStatus::class,
            'to_status' => WorkflowStatus::class,
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
