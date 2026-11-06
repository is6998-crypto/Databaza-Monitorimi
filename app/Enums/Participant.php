<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Participant: string implements HasLabel
{
    use HasTranslatedLabel;

    case Judge = 'judge';
    case Panel = 'panel';
    case Prosecutor = 'prosecutor';
    case DefenceLawyer = 'defence_lawyer';
    case Accused = 'accused';
    case InjuredParty = 'injured_party';
    case Witness = 'witness';
    case Expert = 'expert';
    case Interpreter = 'interpreter';
    case Public = 'public';
    case Media = 'media';
    case Other = 'other';
}
