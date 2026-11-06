<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum StageOutcome: string implements HasLabel
{
    use HasTranslatedLabel;

    case Approved = 'approved';
    case Rejected = 'rejected';
    case Dismissed = 'dismissed';
    case Accepted = 'accepted';
    case Convicting = 'convicting';
    case Acquitting = 'acquitting';
    case Rejecting = 'rejecting';
    case Mixed = 'mixed';
    case Confirmed = 'confirmed';
    case Modified = 'modified';
    case Annulled = 'annulled';
    case ReturnedForRetrial = 'returned_for_retrial';
    case PartiallyApproved = 'partially_approved';
}
