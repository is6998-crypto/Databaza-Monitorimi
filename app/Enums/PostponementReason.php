<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PostponementReason: string implements HasLabel
{
    use HasTranslatedLabel;

    case Prosecutor = 'prosecutor';
    case Judge = 'judge';
    case DefenceLawyer = 'defence_lawyer';
    case Accused = 'accused';
    case InjuredParty = 'injured_party';
    case Expert = 'expert';
    case Other = 'other';
}
