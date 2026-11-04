<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Researcher API: labels in the language asked for with ?lang= or Accept-Language
 * (sq or en), Albanian by default. Codes (enum values, ids) never change.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = array_values((array) config('courtwatch.supported_locales'));
        $requested = $request->query('lang');

        $locale = is_string($requested) && in_array($requested, $supported, true)
            ? $requested
            : $request->getPreferredLanguage($supported);

        app()->setLocale(is_string($locale) && in_array($locale, $supported, true) ? $locale : (string) config('app.locale'));

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());

        return $response;
    }
}
