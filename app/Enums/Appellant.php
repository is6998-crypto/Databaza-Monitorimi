<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Appellant: string implements HasLabel
{
    use HasTranslatedLabel;

    case Prosecutor = 'prosecutor';
    case Defence = 'defence';
    case Accused = 'accused';
    case InjuredParty = 'injured_party';
    case BothParties = 'both_parties';
    case Other = 'other';
}
