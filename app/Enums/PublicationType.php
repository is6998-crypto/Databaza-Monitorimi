<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PublicationType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Report = 'report';
    case Brief = 'brief';
    case Dataset = 'dataset';
    case PressRelease = 'press_release';
}
