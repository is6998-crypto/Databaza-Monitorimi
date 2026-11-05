{{--
    Error pages (proposal B6.10): no technical details, both languages, no external assets
    (they must render even when the database, cache or Vite build are unavailable).
--}}
@php
    $code = (string) ($code ?? 500);
    $locale = in_array(app()->getLocale(), (array) config('courtwatch.supported_locales', ['sq', 'en']), true) ? app()->getLocale() : 'sq';
    $other = $locale === 'sq' ? 'en' : 'sq';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ __('errors.'.$code.'.title', [], $locale) }} · CourtWatch</title>
    <style>
        :root { color-scheme: light dark; --fg: #0f172a; --muted: #475569; --bg: #f8fafc; --card: #ffffff; --accent: #4f46e5; --border: #e2e8f0; }
        @media (prefers-color-scheme: dark) { :root { --fg: #e2e8f0; --muted: #94a3b8; --bg: #0b1120; --card: #111827; --accent: #818cf8; --border: #1f2937; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        main { width: 100%; max-width: 32rem; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; }
        .brand { font-weight: 700; letter-spacing: .02em; color: var(--muted); margin: 0 0 24px; }
        .code { font-size: 3rem; font-weight: 800; line-height: 1; margin: 0; color: var(--accent); }
        h1 { font-size: 1.375rem; margin: 12px 0 8px; }
        p { margin: 0 0 8px; line-height: 1.5; }
        .other { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); color: var(--muted); font-size: .9375rem; }
        .actions { margin-top: 24px; display: flex; gap: 16px; flex-wrap: wrap; }
        a { color: var(--accent); font-weight: 600; text-decoration: none; }
        a:hover, a:focus-visible { text-decoration: underline; }
    </style>
</head>
<body>
    <main>
        <p class="brand">CourtWatch</p>
        <p class="code">{{ $code }}</p>
        <h1>{{ __('errors.'.$code.'.title', [], $locale) }}</h1>
        <p>{{ __('errors.'.$code.'.message', [], $locale) }}</p>
        <div class="other" lang="{{ $other }}">
            <p><strong>{{ __('errors.'.$code.'.title', [], $other) }}</strong> — {{ __('errors.'.$code.'.message', [], $other) }}</p>
        </div>
        <p class="actions">
            <a href="{{ url('/') }}">{{ __('errors.home', [], $locale) }}</a>
        </p>
    </main>
</body>
</html>
