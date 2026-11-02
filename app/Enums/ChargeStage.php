<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ChargeStage: string implements HasLabel
{
    use HasTranslatedLabel;

    case CriminalReport = 'criminal_report';
    case Indictment = 'indictment';
    case Judgment = 'judgment';
}
