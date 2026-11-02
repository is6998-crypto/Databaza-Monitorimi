<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Keyed hashes (HMAC-SHA256) of normalised names, so encrypted party names can be
 * found by exact full name or by individual name tokens without storing plaintext.
 */
final class BlindIndex
{
    public static function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii($value));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $value): array
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_unique(array_filter(explode(' ', $normalized), fn (string $token): bool => strlen($token) >= 2)));
    }

    public static function hash(string $value): string
    {
        return substr(hash_hmac('sha256', $value, self::key()), 0, 24);
    }

    public static function fullName(?string $name): ?string
    {
        if ($name === null || self::normalize($name) === '') {
            return null;
        }

        return self::hash(self::normalize($name));
    }

    /**
     * Space-separated token hashes, e.g. "a1b2… c3d4…".
     */
    public static function tokenString(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $hashes = array_map(fn (string $token): string => self::hash('t:'.$token), self::tokens($name));

        return $hashes === [] ? null : implode(' ', $hashes);
    }

    /**
     * Hashes to look for when searching for $query.
     *
     * @return list<string>
     */
    public static function queryTokenHashes(string $query): array
    {
        return array_map(fn (string $token): string => self::hash('t:'.$token), self::tokens($query));
    }

    private static function key(): string
    {
        $key = (string) config('courtwatch.blind_index_key');

        if ($key === '') {
            $appKey = (string) config('app.key');

            if ($appKey === '') {
                throw new RuntimeException('BLIND_INDEX_KEY (or APP_KEY) must be set.');
            }

            // Derive a separate key so the blind index never reuses the encryption key directly.
            return hash_hmac('sha256', 'courtwatch-blind-index', $appKey);
        }

        return $key;
    }
}
