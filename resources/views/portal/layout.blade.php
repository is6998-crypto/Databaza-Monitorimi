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
    <meta name="theme-color" content="#0c2a42">
    <link rel="icon" href="{{ asset('icons/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/portal.css', 'resources/js/portal.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-canvas text-slate-700 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-navy-900">{{ __('portal.skip') }}</a>

    <header class="bg-navy-900 text-white" x-data="{ open: false }">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('portal.home') }}" class="flex min-w-0 items-center">
                @include('partials.brand')
            </a>
            <nav class="hidden items-center gap-1 xl:flex" aria-label="{{ __('portal.nav.label') }}">
                @foreach ($nav as $name => $label)
                    <a href="{{ route($name) }}" @class(['relative rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition', 'text-white after:absolute after:inset-x-3 after:-bottom-1 after:h-0.5 after:rounded-full after:bg-gold-400' => $routeName === $name, 'text-white/75 hover:bg-white/5 hover:text-white' => $routeName !== $name]) @if ($routeName === $name) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <div class="flex rounded-full border border-white/30 p-0.5 text-xs" role="navigation" aria-label="{{ __('portal.language') }}">
                    @foreach ($supported as $code)
                        <a href="{{ $alternate($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @class(['rounded-full px-2.5 py-1 font-bold uppercase', 'bg-gold-400 text-navy-900' => $code === $locale, 'text-white/80 hover:text-white' => $code !== $locale])>{{ $code }}</a>
                    @endforeach
                </div>
                <button type="button" class="rounded-lg p-2 text-white hover:bg-white/10 xl:hidden" x-on:click="open = !open" :aria-expanded="open" aria-label="{{ __('portal.nav.menu') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        <nav class="border-t border-white/10 px-4 pb-3 pt-2 xl:hidden" x-show="open" x-cloak aria-label="{{ __('portal.nav.label') }}">
            @foreach ($nav as $name => $label)
                <a href="{{ route($name) }}" @class(['block rounded-lg px-3 py-2 text-sm font-semibold', 'bg-white/10 text-white' => $routeName === $name, 'text-white/80 hover:bg-white/5 hover:text-white' => $routeName !== $name])>{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main id="content" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-20 bg-navy-900 text-white/75">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 text-sm sm:grid-cols-3 sm:px-6">
            <div>
                <p class="text-white">@include('partials.brand')</p>
                <p class="mt-4 leading-relaxed">{{ __('portal.footer.about') }}</p>
            </div>
            <div class="space-y-2">
                <a class="block font-semibold text-white/85 hover:text-white hover:underline" href="{{ route('portal.methodology') }}">{{ __('portal.nav.methodology') }}</a>
                <a class="block font-semibold text-white/85 hover:text-white hover:underline" href="{{ route('portal.data-protection') }}">{{ __('portal.nav.data_protection') }}</a>
                <a class="block font-semibold text-white/85 hover:text-white hover:underline" href="{{ route('portal.open-data') }}">{{ __('portal.nav.open_data') }}</a>
            </div>
            <p class="flex gap-3 leading-relaxed"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-gold-400" aria-hidden="true"></span>{{ __('portal.footer.privacy', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
