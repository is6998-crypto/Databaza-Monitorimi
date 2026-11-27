<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * How closely a case should be followed, set by editors: a high-priority case comes first in the case list,
 * the review queue and the monitor app. Internal planning only; never published.
 */
enum CasePriority: string implements HasColor, HasIcon, HasLabel
{
    use HasTranslatedLabel;

    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    /**
     * Position when sorted, highest first.
     */
    public function rank(): int
    {
        return match ($this) {
            self::High => 0,
            self::Normal => 1,
            self::Low => 2,
        };
    }

    /**
     * SQL expression that sorts a priority column highest first (the values are not alphabetical).
     */
    public static function orderSql(string $column = 'priority'): string
    {
        return "CASE {$column} WHEN 'high' THEN 0 WHEN 'low' THEN 2 ELSE 1 END";
    }

    /**
     * The badge only marks what differs from the usual: high (solid yellow, the one that asks for attention) and
     * low (grey). Normal cases show no badge.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::High => 'primary',
            default => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::High => Heroicon::OutlinedFlag,
            self::Normal => Heroicon::OutlinedMinus,
            self::Low => Heroicon::OutlinedArrowDown,
        };
    }
}
