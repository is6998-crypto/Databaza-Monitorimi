<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Who tries the case: the limit for finishing the main trial depends on it (Art. 310 CPC).
 */
enum TrialBench: string implements HasLabel
{
    use HasTranslatedLabel;

    case SingleJudge = 'single_judge';
    case Panel = 'panel';
}
