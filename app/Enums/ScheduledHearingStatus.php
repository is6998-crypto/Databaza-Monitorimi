<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a planned hearing stands. Worked out from the record, never stored.
 */
enum ScheduledHearingStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Upcoming = 'upcoming';
    case Attended = 'attended';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    public function getColor(): string
    {
        return match ($this) {
            self::Upcoming => 'info',
            self::Attended => 'success',
            self::Missed => 'warning',
            self::Cancelled => 'gray',
        };
    }
}
