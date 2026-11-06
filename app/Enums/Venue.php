<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Venue: string implements HasLabel
{
    use HasTranslatedLabel;

    case Courtroom = 'courtroom';
    case Office = 'office';
}
