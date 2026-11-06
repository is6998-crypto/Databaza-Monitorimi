<?php

namespace App\Filament\Admin\Concerns;

use App\Models\User;
use App\Services\FormDrafts;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * Autosave for create/edit pages (proposal B6.4): the form state is stored as an encrypted
 * draft every 20 seconds and right before an idle logout, restored when the page is opened
 * again, and discarded once the record is saved.
 */
trait AutosavesDraft
{
    public ?string $draftSavedAt = null;

    public bool $draftRestored = false;

    abstract protected function draftKey(): string;

    public function autosaveDraft(): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! is_array($this->data)) {
            return;
        }

        FormDrafts::save($user, $this->draftKey(), $this->data);
        $this->draftSavedAt = now()->setTimezone((string) config('courtwatch.display_timezone'))->format('H:i');
    }

    /**
     * Returns the stored draft (and flags it as restored) if there is one.
     *
     * @return array<string, mixed>|null
     */
    protected function restoreDraft(): ?array
    {
        $user = auth()->user();
        $draft = $user instanceof User ? FormDrafts::find($user, $this->draftKey()) : null;

        if ($draft === null) {
            return null;
        }

        $this->draftRestored = true;

        Notification::make()
            ->title(__('cases.drafts.restored', ['time' => $draft->updated_at?->setTimezone((string) config('courtwatch.display_timezone'))->format('d.m.Y H:i')]))
            ->info()
            ->send();

        return $draft->data;
    }

    protected function forgetDraft(): void
    {
        $user = auth()->user();

        if ($user instanceof User) {
            FormDrafts::forget($user, $this->draftKey());
        }

        $this->draftSavedAt = null;
        $this->draftRestored = false;
    }

    protected function discardDraftAction(): Action
    {
        return Action::make('discardDraft')
            ->label(__('cases.drafts.discard'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (): bool => $this->draftRestored)
            ->action(function (): void {
                $this->forgetDraft();
                $this->redirect(request()->header('Referer') ?? url()->current());
            });
    }

    public function getFooter(): ?View
    {
        return view('filament.admin.autosave', ['savedAt' => $this->draftSavedAt]);
    }
}
