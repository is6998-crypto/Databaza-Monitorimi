<?php

namespace App\Livewire\Portal\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;

/**
 * Livewire update requests do not carry the /{locale} prefix, so the component remembers
 * the language it was rendered in.
 */
trait KeepsPortalLocale
{
    #[Locked]
    public string $locale = 'sq';

    public function mountKeepsPortalLocale(): void
    {
        // First render: the portal middleware already set the locale from the URL.
        $this->locale = in_array(app()->getLocale(), (array) config('courtwatch.supported_locales'), true)
            ? app()->getLocale()
            : (string) config('app.locale');

        URL::defaults(['locale' => $this->locale]);
    }

    public function hydrateKeepsPortalLocale(): void
    {
        if (in_array($this->locale, (array) config('courtwatch.supported_locales'), true)) {
            app()->setLocale($this->locale);
            Carbon::setLocale($this->locale);
            URL::defaults(['locale' => $this->locale]);
        }
    }
}
