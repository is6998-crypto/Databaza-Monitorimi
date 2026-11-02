<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Every export (staff, researcher, API, public open data) is recorded here.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $context
 * @property string $resource
 * @property string $format
 * @property array<string, mixed>|null $filters
 * @property int $row_count
 * @property int|null $token_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class ExportLog extends Model
{
    public const UPDATED_AT = null;

    public const CONTEXT_ADMIN = 'admin';

    public const CONTEXT_RESEARCHER = 'researcher';

    public const CONTEXT_API = 'api';

    public const CONTEXT_PUBLIC = 'public';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'row_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
