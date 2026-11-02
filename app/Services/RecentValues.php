<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRecentValue;
use Illuminate\Support\Facades\DB;

/**
 * Tracks recently used reference values per user so searchable selects can show them first.
 */
class RecentValues
{
    public const LIMIT = 6;

    public static function remember(?User $user, string $field, ?int $valueId): void
    {
        if ($user === null || $valueId === null) {
            return;
        }

        DB::table('user_recent_values')->upsert(
            [['user_id' => $user->id, 'field' => $field, 'value_id' => $valueId, 'used_at' => now()]],
            ['user_id', 'field', 'value_id'],
            ['used_at'],
        );
    }

    /**
     * @return list<int>
     */
    public static function ids(?User $user, string $field, int $limit = self::LIMIT): array
    {
        if ($user === null) {
            return [];
        }

        return UserRecentValue::query()
            ->where('user_id', $user->id)
            ->where('field', $field)
            ->orderByDesc('used_at')
            ->limit($limit)
            ->pluck('value_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Groups options as [Recently used => [...], All => [...]] when the user has recent values.
     *
     * @param  array<int|string, string>  $options
     * @return array<int|string, string>|array<string, array<int|string, string>>
     */
    public static function group(?User $user, string $field, array $options): array
    {
        $recentIds = array_values(array_filter(self::ids($user, $field), fn (int $id): bool => array_key_exists($id, $options)));

        if ($recentIds === []) {
            return $options;
        }

        $recent = [];

        foreach ($recentIds as $id) {
            $recent[$id] = $options[$id];
        }

        return [
            __('app.recently_used') => $recent,
            __('app.all_options') => array_diff_key($options, $recent),
        ];
    }
}
