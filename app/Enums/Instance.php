<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Instance: string implements HasLabel
{
    use HasTranslatedLabel;

    case FirstInstance = 'first_instance';
    case Appeal = 'appeal';
    case Supreme = 'supreme';
}
