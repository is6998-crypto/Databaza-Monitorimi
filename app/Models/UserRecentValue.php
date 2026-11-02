<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Remembers which reference values a user picked recently, so selects can list them first.
 *
 * @property int $user_id
 * @property string $field
 * @property int $value_id
 * @property Carbon $used_at
 */
class UserRecentValue extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
