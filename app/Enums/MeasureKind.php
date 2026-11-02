<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeasureKind: string implements HasLabel
{
    use HasTranslatedLabel;

    case SecurityMeasure = 'security_measure';
    case Seizure = 'seizure';
    case Confiscation = 'confiscation';
}
