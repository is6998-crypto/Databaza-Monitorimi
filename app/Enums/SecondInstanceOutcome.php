<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SecondInstanceOutcome: string implements HasLabel
{
    use HasTranslatedLabel;

    case Approved = 'approved';
    case Rejected = 'rejected';
    case PartiallyApproved = 'partially_approved';
    case Annulled = 'annulled';
    case NoDecision = 'no_decision';
}
