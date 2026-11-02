<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Autosaved, not-yet-submitted form state (one row per user and form).
 *
 * @property int $id
 * @property int $user_id
 * @property string $form_key
 * @property array<string, mixed> $data
 * @property Carbon $updated_at
 */
class FormDraft extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
        ];
    }
}
