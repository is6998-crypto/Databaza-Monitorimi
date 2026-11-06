<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff/researcher/monitor areas: locale from the session, then the user's preference, then the default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('courtwatch.supported_locales');
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale ??= $request->user()?->locale;

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = (string) config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
