<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ExportFormat: string implements HasLabel
{
    use HasTranslatedLabel;

    case Csv = 'csv';
    case Xlsx = 'xlsx';
}
