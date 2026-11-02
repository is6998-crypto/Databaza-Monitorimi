<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum HearingType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Initial = 'initial';
    case Second = 'second';
    case MainTrial = 'main_trial';
    case ClosingArguments = 'closing_arguments';
    case JudgmentAnnouncement = 'judgment_announcement';
    case AppealSession = 'appeal_session';
    case Preparatory = 'preparatory';
    case Other = 'other';
}
