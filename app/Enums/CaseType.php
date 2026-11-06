<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CaseType: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case CriminalCorruption = 'criminal_corruption';
    case CriminalSerious = 'criminal_serious';
    case CriminalGeneral = 'criminal_general';
    case CriminalSpecial = 'criminal_special';
    case Administrative = 'administrative';
    case Civil = 'civil';
    case Economic = 'economic';

    public function isCriminal(): bool
    {
        return str_starts_with($this->value, 'criminal_');
    }

    /**
     * Civil and economic cases share the civil details table.
     */
    public function usesCivilDetails(): bool
    {
        return in_array($this, [self::Civil, self::Economic], true);
    }

    /**
     * Case types are categories, not states: they share one quiet badge style, so the
     * workflow status next to them stands out.
     */
    public function getColor(): string
    {
        return 'info';
    }

    /**
     * @return list<self>
     */
    public static function criminal(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->isCriminal()));
    }
}
