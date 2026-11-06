<?php

namespace App\Services;

use App\Models\FormDraft;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Autosaved form drafts (encrypted at rest, one per user and form).
 */
class FormDrafts
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(User $user, string $key, array $data): FormDraft
    {
        return FormDraft::query()->updateOrCreate(
            ['user_id' => $user->id, 'form_key' => $key],
            ['data' => self::serializable($data)],
        );
    }

    public static function find(User $user, string $key): ?FormDraft
    {
        return FormDraft::query()->where('user_id', $user->id)->where('form_key', $key)->first();
    }

    public static function forget(User $user, string $key): void
    {
        FormDraft::query()->where('user_id', $user->id)->where('form_key', $key)->delete();
    }

    /**
     * Drops values that cannot be stored as JSON (e.g. temporary file uploads).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function serializable(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::serializable($value);
            } elseif ($value instanceof \BackedEnum) {
                $clean[$key] = $value->value;
            } elseif ($value instanceof Arrayable) {
                $clean[$key] = $value->toArray();
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
