{{--
    Error pages (proposal B6.10): no technical details, no external assets (they must render even when the
    database, cache or Vite build are unavailable). One language, the page's: the /sq or /en prefix of the
    address, else the visitor's saved choice, else the default (Albanian).
--}}
@php
    $code = (string) ($code ?? 500);
    $supported = (array) config('courtwatch.supported_locales', ['sq', 'en']);
    $fromUrl = request()->segment(1);
    $fromSession = rescue(fn () => request()->hasSession() ? request()->session()->get('locale') : null, null, false);
    $locale = collect([$fromUrl, $fromSession, app()->getLocale()])->first(fn ($candidate) => in_array($candidate, $supported, true)) ?? 'sq';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ __('errors.'.$code.'.title', [], $locale) }} · CourtWatch</title>
    <style>
        :root { color-scheme: light dark; --fg: #0c2a42; --muted: #4b5768; --bg: #f5f6f7; --band: #0c2a42; --card: #ffffff; --accent: #0c2a42; --mark: #f8c33a; --border: #e2e5ea; }
        @media (prefers-color-scheme: dark) { :root { --fg: #eef3f8; --muted: #94a0ae; --bg: #071b2c; --band: #040f19; --card: #0c2a42; --accent: #f8c33a; --border: #183852; } }
        * { box-sizing: border-box; }
        /* A navy band behind the top of the card, as on the sign-in pages and the portal. */
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: linear-gradient(var(--band) 0 38vh, var(--bg) 38vh); color: var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        main { width: 100%; max-width: 32rem; background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 32px; box-shadow: 0 24px 48px -24px rgb(12 42 66 / .35); }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; letter-spacing: -.01em; margin: 0 0 28px; }
        .brand svg { width: 28px; height: 28px; flex: none; }
        .code { font-size: 3.5rem; font-weight: 800; letter-spacing: -.03em; line-height: 1; margin: 0; color: var(--accent); }
        h1 { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; margin: 14px 0 8px; }
        p { margin: 0 0 8px; line-height: 1.55; }
        .actions { margin-top: 24px; display: flex; gap: 16px; flex-wrap: wrap; }
        a { color: var(--fg); font-weight: 700; text-decoration: underline; text-decoration-color: var(--mark); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        a:hover, a:focus-visible { text-decoration-color: currentColor; }
        a.button { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 20px; border-radius: 12px; background: var(--mark); color: #0c2a42; text-decoration: none; transition: background-color 150ms, translate 150ms; }
        a.button:hover { background: #fbd35c; translate: 0 -1px; }
        a.button:active { translate: 0 1px; }
        a.button:focus-visible { outline: 2px solid var(--fg); outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) { a.button, a.button:hover, a.button:active { transition: none; translate: none; } }
    </style>
</head>
<body>
    <main>
        <p class="brand"><svg viewBox="0 0 40 40" aria-hidden="true" focusable="false"><path fill="#f8c33a" fill-rule="evenodd" d="M10 4h26v26l-6 6H4V10zM9.5 9.5v21h21v-21z"/></svg>CourtWatch</p>
        <p class="code">{{ $code }}</p>
        <h1>{{ __('errors.'.$code.'.title', [], $locale) }}</h1>
        <p>{{ __('errors.'.$code.'.message', [], $locale) }}</p>
        <p class="actions">
            <a class="button" href="{{ url('/'.$locale) }}">{{ __('errors.home', [], $locale) }}</a>
        </p>
    </main>
</body>
</html>
