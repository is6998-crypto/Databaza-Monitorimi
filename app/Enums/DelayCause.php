<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum DelayCause: string implements HasLabel
{
    use HasTranslatedLabel;

    case Panel = 'panel';
    case Prosecutor = 'prosecutor';
    case Defence = 'defence';
    case Accused = 'accused';
    case Witness = 'witness';
    case Other = 'other';
}
