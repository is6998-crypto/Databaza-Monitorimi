<?php

namespace App\Filament\Research\Pages;

use App\Enums\ResearcherStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * The data-use agreement every approved researcher must accept (versioned).
 */
class DataUseAgreement extends Page
{
    protected string $view = 'filament.research.data-use-agreement';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'data-use-agreement';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->researcherProfile?->status === ResearcherStatus::Approved;
    }

    public function getTitle(): string
    {
        return __('research.agreement.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('research.agreement.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('accept')
                ->label(__('research.agreement.accept'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => ! (auth()->user() instanceof User && auth()->user()->researcherProfile?->hasAcceptedCurrentAgreement()))
                ->schema([
                    Checkbox::make('confirm')->label(__('research.agreement.confirm'))->accepted(),
                ])
                ->action(function (): void {
                    $user = auth()->user();
                    abort_unless($user instanceof User && static::canAccess(), 403);

                    $profile = $user->researcherProfile;
                    $profile?->update([
                        'agreement_version' => config('courtwatch.data_use_agreement_version'),
                        'agreement_accepted_at' => now(),
                    ]);

                    Notification::make()->title(__('research.agreement.accepted'))->success()->send();
                    $this->redirect(Datasets::getUrl());
                }),
        ];
    }
}
