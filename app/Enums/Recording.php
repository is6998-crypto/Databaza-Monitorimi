<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Recording: string implements HasLabel
{
    use HasTranslatedLabel;

    case None = 'none';
    case Audio = 'audio';
    case Video = 'video';
    case AudioVideo = 'audio_video';
}
