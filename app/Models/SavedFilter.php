<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $resource
 * @property string $name
 * @property array<string, mixed> $filters
 * @property bool $is_shared
 * @property-read User $user
 */
class SavedFilter extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * Own filters plus filters shared by colleagues.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAvailableTo(Builder $query, User $user, string $resource): void
    {
        $query->where('resource', $resource)
            ->where(fn (Builder $query) => $query->where('user_id', $user->id)->orWhere('is_shared', true))
            ->orderBy('name');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
