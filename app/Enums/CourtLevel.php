<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum CourtLevel: string implements HasLabel
{
    use HasTranslatedLabel;

    case Basic = 'basic';
    case Appeal = 'appeal';
    case Supreme = 'supreme';
}
