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

    public function getColor(): string
    {
        return match ($this) {
            self::CriminalCorruption => 'danger',
            self::CriminalSerious => 'warning',
            self::CriminalGeneral => 'gray',
            self::CriminalSpecial => 'primary',
            self::Administrative => 'info',
            self::Civil => 'success',
            self::Economic => 'success',
        };
    }

    /**
     * @return list<self>
     */
    public static function criminal(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->isCriminal()));
    }
}
