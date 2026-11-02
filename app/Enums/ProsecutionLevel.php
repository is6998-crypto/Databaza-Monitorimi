<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ProsecutionLevel: string implements HasLabel
{
    use HasTranslatedLabel;

    case Basic = 'basic';
    case Special = 'special';
    case Appellate = 'appellate';
    case State = 'state';
}
