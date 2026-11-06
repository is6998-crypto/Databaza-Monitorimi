<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ClaimType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Property = 'property';
    case Labour = 'labour';
    case Family = 'family';
    case Contract = 'contract';
    case Damages = 'damages';
    case Inheritance = 'inheritance';
    case Other = 'other';
}
