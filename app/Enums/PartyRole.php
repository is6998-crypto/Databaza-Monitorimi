<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PartyRole: string implements HasLabel
{
    use HasTranslatedLabel;

    case Accused = 'accused';
    case LegalEntity = 'legal_entity';
    case Plaintiff = 'plaintiff';
    case Respondent = 'respondent';
    case InjuredParty = 'injured_party';
}
