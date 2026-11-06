<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LegalRemedyType: string implements HasLabel
{
    use HasTranslatedLabel;

    case AgainstDecision = 'against_decision';
    case AgainstSilence = 'against_silence';
    case None = 'none';
}
