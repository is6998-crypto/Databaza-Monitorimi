<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel
{
    use HasTranslatedLabel;

    case Male = 'male';
    case Female = 'female';
    case LegalPerson = 'legal_person';
    case Other = 'other';
}
