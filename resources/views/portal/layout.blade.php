@php
    $locale = app()->getLocale();
    $supported = (array) config('courtwatch.supported_locales');
    $routeName = request()->route()?->getName();
    $routeParameters = request()->route()?->parameters() ?? [];
    $alternate = fn (string $code) => $routeName ? route($routeName, array_merge($routeParameters, ['locale' => $code])) : url('/'.$code);
    $pageTitle = trim(($title ?? '') !== '' ? $title.' · CourtWatch' : 'CourtWatch — '.__('portal.tagline'));
    $pageDescription = $description ?? __('portal.meta_description');
    $nav = [
        'portal.home' => __('portal.nav.home'),
        'portal.cases' => __('portal.nav.cases'),
        'portal.publications' => __('portal.nav.publications'),
        'portal.open-data' => __('portal.nav.open_data'),
        'portal.methodology' => __('portal.nav.methodology'),
        'portal.about' => __('portal.nav.about'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @foreach ($supported as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $alternate($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alternate((string) config('app.locale')) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale === 'sq' ? 'sq_AL' : 'en_GB' }}">
    <meta name="theme-color" content="#4338ca">
    <link rel="icon" href="{{ asset('icons/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/portal.css', 'resources/js/portal.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-white text-slate-800 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded focus:bg-white focus:px-3 focus:py-2">{{ __('portal.skip') }}</a>

    <header class="border-b border-slate-200 bg-white" x-data="{ open: false }">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('portal.home') }}" class="flex items-center gap-2 font-semibold text-indigo-800">
                <img src="{{ asset('icons/favicon.svg') }}" alt="" class="size-8">
                <span class="text-lg">CourtWatch</span>
            </a>
            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('portal.nav.label') }}">
                @foreach ($nav as $name => $label)
                    <a href="{{ route($name) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium', 'bg-indigo-50 text-indigo-800' => $routeName === $name, 'text-slate-700 hover:bg-slate-100' => $routeName !== $name])>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <div class="flex rounded-lg border border-slate-200 p-0.5 text-sm" role="navigation" aria-label="{{ __('portal.language') }}">
                    @foreach ($supported as $code)
                        <a href="{{ $alternate($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @class(['rounded-md px-2 py-1 font-semibold uppercase', 'bg-indigo-700 text-white' => $code === $locale, 'text-slate-600 hover:bg-slate-100' => $code !== $locale])>{{ $code }}</a>
                    @endforeach
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden" x-on:click="open = !open" :aria-expanded="open" aria-label="{{ __('portal.nav.menu') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        <nav class="border-t border-slate-100 px-4 pb-3 lg:hidden" x-show="open" x-cloak aria-label="{{ __('portal.nav.label') }}">
            @foreach ($nav as $name => $label)
                <a href="{{ route($name) }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main id="content" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-slate-50">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm text-slate-600 sm:grid-cols-3 sm:px-6">
            <div>
                <p class="font-semibold text-slate-800">CourtWatch</p>
                <p class="mt-1">{{ __('portal.footer.about') }}</p>
            </div>
            <div class="space-y-1">
                <a class="block hover:underline" href="{{ route('portal.methodology') }}">{{ __('portal.nav.methodology') }}</a>
                <a class="block hover:underline" href="{{ route('portal.data-protection') }}">{{ __('portal.nav.data_protection') }}</a>
                <a class="block hover:underline" href="{{ route('portal.open-data') }}">{{ __('portal.nav.open_data') }}</a>
            </div>
            <p>{{ __('portal.footer.privacy', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
