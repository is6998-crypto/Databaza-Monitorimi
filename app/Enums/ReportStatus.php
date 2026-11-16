<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * A hearing report: draft → submitted → approved, plus returned (the editor asks for more).
 */
enum ReportStatus: string implements HasColor, HasIcon, HasLabel
{
    use HasTranslatedLabel;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Approved = 'approved';

    /**
     * Reports the monitor who wrote them may still change.
     */
    public function isEditableByAuthor(): bool
    {
        return $this !== self::Approved;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Returned => 'warning',
            self::Approved => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Submitted => Heroicon::OutlinedPaperAirplane,
            self::Returned => Heroicon::OutlinedArrowUturnLeft,
            self::Approved => Heroicon::OutlinedCheckBadge,
        };
    }
}
