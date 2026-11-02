<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum CivilOutcome: string implements HasLabel
{
    use HasTranslatedLabel;

    case Granted = 'granted';
    case Rejected = 'rejected';
    case PartiallyGranted = 'partially_granted';
    case Settled = 'settled';
    case Withdrawn = 'withdrawn';
    case Pending = 'pending';
}
