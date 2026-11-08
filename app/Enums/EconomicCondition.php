<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/** Economic condition of a party (separate from the employment status list). */
enum EconomicCondition: string implements HasLabel
{
    use HasTranslatedLabel;

    case NoData = 'no_data';
    case Poor = 'poor';
    case Average = 'average';
    case High = 'high';
    case SocialAssistance = 'social_assistance';
    case Other = 'other';
}
