<?php

namespace App\Filament\Research\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Researcher home: account status (pending, approved, rejected) and next steps.
 */
class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.research.dashboard';

    public function getTitle(): string
    {
        return __('research.dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('research.dashboard.title');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $profile = $user instanceof User ? $user->researcherProfile : null;

        return [
            'profile' => $profile,
            'approved' => $user instanceof User && $user->isApprovedResearcher(),
            'needsAgreement' => $profile !== null && $profile->status->value === 'approved' && ! $profile->hasAcceptedCurrentAgreement(),
        ];
    }

    public function getWidgets(): array
    {
        return [];
    }
}
