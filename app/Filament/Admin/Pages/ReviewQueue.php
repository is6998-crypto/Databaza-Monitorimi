<?php

namespace App\Filament\Admin\Pages;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Widgets\Review\CasesAwaitingReview;
use App\Filament\Admin\Widgets\Review\HearingsAwaitingReview;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Support\Permissions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Editors' review queue: everything submitted for review, oldest first.
 */
class ReviewQueue extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'review-queue';

    public static function canAccess(): bool
    {
        return auth()->user()?->checkPermissionTo(Permissions::REVIEW) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('workflow.queue.title');
    }

    public function getTitle(): string
    {
        return __('workflow.queue.title');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = CourtCase::query()->awaitingReview()->count() + Hearing::query()->awaitingReview()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CasesAwaitingReview::class,
            HearingsAwaitingReview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
