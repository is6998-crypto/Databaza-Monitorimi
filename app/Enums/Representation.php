<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Representation: string implements HasLabel
{
    use HasTranslatedLabel;

    case Self = 'self';
    case Lawyer = 'lawyer';
    case Ngo = 'ngo';
    case Ombudsperson = 'ombudsperson';
    case None = 'none';
}
