<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PhoneUser: string implements HasLabel
{
    use HasTranslatedLabel;

    case Judge = 'judge';
    case Prosecutor = 'prosecutor';
    case DefenceLawyer = 'defence_lawyer';
    case Accused = 'accused';
    case InjuredParty = 'injured_party';
    case Public = 'public';
    case Other = 'other';
}
