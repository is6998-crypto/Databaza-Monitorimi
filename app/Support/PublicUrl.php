<?php

namespace App\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\URL;

/**
 * The address that generated links (redirects, styles, scripts, forms) use.
 *
 * - Production behind TLS: always https.
 * - Development behind an HTTPS forwarding address, such as GitHub Codespaces: the request
 *   reaches the dev server as http://localhost:8000, so links are built from APP_URL (the
 *   dev container sets it to the Codespace's address) instead of from the request, which
 *   would send the browser to the visitor's own computer.
 */
final class PublicUrl
{
    public static function configure(Application $app): void
    {
        $url = rtrim((string) config('app.url'), '/');

        if (! str_starts_with($url, 'https://')) {
            return;
        }

        if ($app->isProduction()) {
            URL::forceScheme('https');

            return;
        }

        if ($app->environment('local')) {
            URL::forceRootUrl($url);
            URL::forceScheme('https');
        }
    }
}
