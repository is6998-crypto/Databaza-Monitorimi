<?php

namespace App\Services;

/**
 * Small-cell suppression for everything shown or downloaded publicly (proposal B6.7):
 * any aggregate cell under the threshold (5) is replaced by null and shown as "<5".
 * Rates and medians are suppressed when they rest on fewer than 5 records.
 */
final class Anonymizer
{
    public static function threshold(): int
    {
        return (int) config('courtwatch.suppression_threshold', 5);
    }

    /**
     * A count, or null when it is below the threshold.
     */
    public static function suppress(int|float|null $count): int|float|null
    {
        if ($count === null) {
            return null;
        }

        return $count < self::threshold() ? null : $count;
    }

    /**
     * A percentage of $part in $total, or null if either is below the threshold.
     */
    public static function rate(int $part, int $total, int $precision = 1): ?float
    {
        if ($part < self::threshold() || $total < self::threshold()) {
            return null;
        }

        return round($part / $total * 100, $precision);
    }

    /**
     * A statistic computed over $n records (median, average), or null if $n is too small.
     */
    public static function statistic(int|float|null $value, int $n): int|float|null
    {
        return $n < self::threshold() ? null : $value;
    }

    /**
     * Suppresses every value of a [key => count] map.
     *
     * @param  array<string, int|float>  $counts
     * @return array<string, int|float|null>
     */
    public static function suppressAll(array $counts): array
    {
        return array_map(fn (int|float $count): int|float|null => self::suppress($count), $counts);
    }

    /**
     * Display value: the number, or "<5".
     */
    public static function display(int|float|null $value, string $suffix = ''): string
    {
        if ($value === null) {
            return '<'.self::threshold();
        }

        $formatted = is_float($value) && floor($value) !== $value
            ? number_format($value, 1, ',', '.')
            : number_format((float) $value, 0, ',', '.');

        return $formatted.$suffix;
    }
}
