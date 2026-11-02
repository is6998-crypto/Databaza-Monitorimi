<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SentenceKind: string implements HasLabel
{
    use HasTranslatedLabel;

    case Requested = 'requested';
    case Imposed = 'imposed';
}
