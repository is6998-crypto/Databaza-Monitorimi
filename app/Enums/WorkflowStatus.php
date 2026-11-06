<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * draft → submitted → in_review → approved → published, plus returned (with a comment).
 */
enum WorkflowStatus: string implements HasColor, HasIcon, HasLabel
{
    use HasTranslatedLabel;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Published = 'published';
    case Returned = 'returned';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft, self::Returned => [self::Submitted],
            self::Submitted => [self::InReview, self::Approved, self::Returned],
            self::InReview => [self::Approved, self::Returned],
            self::Approved => [self::Published, self::Returned],
            self::Published => [self::Approved],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Records the author may still edit.
     */
    public function isEditableByAuthor(): bool
    {
        return in_array($this, [self::Draft, self::Returned, self::Submitted, self::InReview], true);
    }

    /**
     * Records waiting for an editor.
     */
    public function isAwaitingReview(): bool
    {
        return in_array($this, [self::Submitted, self::InReview], true);
    }

    /**
     * Verified data that feeds dashboards, the public portal and researchers.
     */
    public function isVerified(): bool
    {
        return in_array($this, [self::Approved, self::Published], true);
    }

    /**
     * @return list<string>
     */
    public static function verifiedValues(): array
    {
        return [self::Approved->value, self::Published->value];
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::InReview => 'warning',
            self::Approved => 'success',
            self::Published => 'primary',
            self::Returned => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Submitted => Heroicon::OutlinedPaperAirplane,
            self::InReview => Heroicon::OutlinedEye,
            self::Approved => Heroicon::OutlinedCheckBadge,
            self::Published => Heroicon::OutlinedGlobeAlt,
            self::Returned => Heroicon::OutlinedArrowUturnLeft,
        };
    }
}
