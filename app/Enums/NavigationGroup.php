<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Staff panel navigation groups, in display order.
 */
enum NavigationGroup: string implements HasLabel
{
    use HasTranslatedLabel;

    case Cases = 'cases';
    case Analytics = 'analytics';
    case Portal = 'portal';
    case ReferenceData = 'reference_data';
    case Administration = 'administration';
}
