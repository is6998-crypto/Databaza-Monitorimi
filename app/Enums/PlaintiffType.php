<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PlaintiffType: string implements HasLabel
{
    use HasTranslatedLabel;

    case NaturalPerson = 'natural_person';
    case LegalPerson = 'legal_person';
    case Ngo = 'ngo';
    case Ombudsperson = 'ombudsperson';
}
