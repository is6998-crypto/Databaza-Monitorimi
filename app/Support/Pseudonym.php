<?php

namespace App\Support;

/**
 * Stable, non-reversible identifiers for anonymized research data (e.g. "P-3f9a1c0b2e4d").
 */
final class Pseudonym
{
    public static function for(string $kind, int|string $id): string
    {
        $key = (string) (config('courtwatch.blind_index_key') ?: config('app.key'));

        return strtoupper(substr($kind, 0, 1)).'-'.substr(hash_hmac('sha256', $kind.':'.$id, 'research|'.$key), 0, 12);
    }
}
