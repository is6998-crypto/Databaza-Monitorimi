<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response (proposal B6.10): Content-Security-Policy, HSTS (HTTPS
 * only), X-Frame-Options, Referrer-Policy, X-Content-Type-Options and Permissions-Policy.
 *
 * The CSP only allows this site's own origin (plus the S3 storage origin for file previews).
 * 'unsafe-inline' and 'unsafe-eval' are needed by Livewire/Alpine and Filament, which
 * evaluate expressions at runtime and inline small scripts and styles.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && (bool) config('courtwatch.security.hsts')) {
            $headers->set('Strict-Transport-Security', 'max-age='.(int) config('courtwatch.security.hsts_max_age', 31536000).'; includeSubDomains');
        }

        $mode = (string) config('courtwatch.security.csp', 'enforce');

        if ($mode !== 'off' && ! $headers->has('Content-Security-Policy')) {
            $headers->set($mode === 'report-only' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $this->contentSecurityPolicy($request));
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $self = "'self'";
        $storage = $this->storageOrigins();
        $dev = $this->viteDevServer();

        $directives = [
            'default-src' => [$self],
            'script-src' => [$self, "'unsafe-inline'", "'unsafe-eval'", ...$dev],
            'style-src' => [$self, "'unsafe-inline'", ...$dev],
            'img-src' => [$self, 'data:', 'blob:', ...$storage],
            'font-src' => [$self, 'data:', ...$dev],
            'connect-src' => [$self, ...$storage, ...$dev, ...array_map(fn (string $origin): string => preg_replace('#^http#', 'ws', $origin) ?? $origin, $dev)],
            'media-src' => [$self, ...$storage],
            'frame-src' => [$self],
            'frame-ancestors' => [$self],
            'worker-src' => [$self, 'blob:'],
            'manifest-src' => [$self],
            'object-src' => ["'none'"],
            'base-uri' => [$self],
            'form-action' => [$self],
        ];

        $policy = implode('; ', array_map(
            fn (string $directive, array $sources): string => $directive.' '.implode(' ', array_values(array_unique($sources))),
            array_keys($directives),
            $directives,
        ));

        return $request->isSecure() ? $policy.'; upgrade-insecure-requests' : $policy;
    }

    /**
     * Origin of the S3-compatible storage, when files are previewed straight from it.
     *
     * @return list<string>
     */
    private function storageOrigins(): array
    {
        $origins = [];

        foreach (array_unique([(string) config('filesystems.default'), (string) config('courtwatch.attachments.disk')]) as $disk) {
            if (config("filesystems.disks.{$disk}.driver") !== 's3') {
                continue;
            }

            foreach (['url', 'endpoint'] as $key) {
                $origin = $this->origin((string) config("filesystems.disks.{$disk}.{$key}"));

                if ($origin !== null) {
                    $origins[] = $origin;
                }
            }
        }

        return array_values(array_unique($origins));
    }

    /**
     * The Vite dev server (local development with `npm run dev` only).
     *
     * @return list<string>
     */
    private function viteDevServer(): array
    {
        if (! app()->isLocal() || ! Vite::isRunningHot()) {
            return [];
        }

        $origin = $this->origin(trim((string) file_get_contents(public_path('hot'))));

        return $origin === null ? [] : [$origin];
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
