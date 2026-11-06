<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum CounselType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Retained = 'retained';
    case ExOfficio = 'ex_officio';
    case None = 'none';
}
