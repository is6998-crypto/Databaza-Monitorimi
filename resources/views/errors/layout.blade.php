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
        :root { color-scheme: light dark; --fg: #0c2a42; --muted: #4b5768; --bg: #f5f6f7; --card: #ffffff; --accent: #0c2a42; --mark: #f8c33a; --border: #e2e5ea; }
        @media (prefers-color-scheme: dark) { :root { --fg: #eef3f8; --muted: #94a0ae; --bg: #071b2c; --card: #0c2a42; --accent: #f8c33a; --border: #183852; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        main { width: 100%; max-width: 32rem; background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 32px; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; letter-spacing: -.01em; margin: 0 0 28px; }
        .brand svg { width: 28px; height: 28px; flex: none; }
        .code { font-size: 3.5rem; font-weight: 800; letter-spacing: -.03em; line-height: 1; margin: 0; color: var(--accent); }
        h1 { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; margin: 14px 0 8px; }
        p { margin: 0 0 8px; line-height: 1.55; }
        .other { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); color: var(--muted); font-size: .9375rem; }
        .actions { margin-top: 24px; display: flex; gap: 16px; flex-wrap: wrap; }
        a { color: var(--fg); font-weight: 700; text-decoration: underline; text-decoration-color: var(--mark); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        a:hover, a:focus-visible { text-decoration-color: currentColor; }
    </style>
</head>
<body>
    <main>
        <p class="brand"><svg viewBox="0 0 40 40" aria-hidden="true" focusable="false"><path d="M7.5 7.5h19v6h6v19h-19v-6h-6z" fill="none" stroke="#f8c33a" stroke-width="5"/></svg>CourtWatch</p>
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
