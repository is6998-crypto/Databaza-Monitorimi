<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    use HasTranslatedLabel;

    case SuperAdmin = 'super_admin';
    case Editor = 'editor';
    case Monitor = 'monitor';
    case Analyst = 'analyst';
    case Researcher = 'researcher';

    /**
     * Roles that work inside the staff panel (/admin) and must use 2FA.
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::SuperAdmin, self::Editor, self::Monitor, self::Analyst];
    }

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::staff());
    }
}
