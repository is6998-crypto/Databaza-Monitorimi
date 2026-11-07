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
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-gold-400 focus:px-4 focus:py-2.5 focus:font-bold focus:text-navy-900">{{ __('portal.skip') }}</a>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-navy-900 text-white" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
        <div class="portal-wrap flex h-16 items-center justify-between gap-4 lg:h-[72px]">
            <a href="{{ route('portal.home') }}" class="flex min-w-0 items-center rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-400">
                @include('partials.brand')
            </a>
            <nav class="hidden h-full items-stretch gap-1 xl:flex" aria-label="{{ __('portal.nav.label') }}">
                @foreach ($nav as $name => $label)
                    <a href="{{ route($name) }}" @class(['group relative flex items-center px-3 text-[15px] font-semibold whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:-outline-offset-8 focus-visible:outline-gold-400', 'text-white' => $routeName === $name, 'text-white/75 hover:text-white' => $routeName !== $name]) @if ($routeName === $name) aria-current="page" @endif>
                        {{ $label }}
                        <span @class(['absolute inset-x-3 bottom-0 h-[3px] rounded-t-full bg-gold-400 transition-transform duration-150 ease-out', 'scale-x-100' => $routeName === $name, 'origin-left scale-x-0 bg-white/40 group-hover:scale-x-100' => $routeName !== $name]) aria-hidden="true"></span>
                    </a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <div class="flex rounded-full border border-white/25 p-1 text-[13px]" role="navigation" aria-label="{{ __('portal.language') }}">
                    @foreach ($supported as $code)
                        <a href="{{ $alternate($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @class(['flex min-h-9 min-w-10 items-center justify-center rounded-full px-2.5 font-bold uppercase transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gold-400', 'bg-gold-400 text-navy-900' => $code === $locale, 'text-white/80 hover:bg-white/10 hover:text-white' => $code !== $locale]) @if ($code === $locale) aria-current="true" @endif>{{ $code }}</a>
                    @endforeach
                </div>
                <button type="button" class="flex size-11 items-center justify-center rounded-xl border border-white/25 text-white transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400 xl:hidden" x-on:click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="portal-menu" aria-label="{{ __('portal.nav.menu') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path x-show="!open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>
        </div>
        <nav id="portal-menu" class="border-t border-white/10 xl:hidden" x-show="open" x-cloak x-transition.opacity.duration.150ms aria-label="{{ __('portal.nav.label') }}">
            <div class="portal-wrap grid gap-1 py-3">
                @foreach ($nav as $name => $label)
                    <a href="{{ route($name) }}" @class(['flex min-h-12 items-center justify-between rounded-xl px-3 text-base font-semibold', 'bg-white/10 text-white' => $routeName === $name, 'text-white/80 hover:bg-white/5 hover:text-white' => $routeName !== $name]) @if ($routeName === $name) aria-current="page" @endif>
                        {{ $label }}
                        @if ($routeName === $name)<span class="size-2 rounded-full bg-gold-400" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </div>
        </nav>
    </header>

    <main id="content" class="flex-1" tabindex="-1">
        @yield('content')
    </main>

    <footer class="mt-24 bg-navy-900 text-white/75">
        <div class="portal-wrap grid gap-10 py-14 text-[15px] sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1.2fr)]">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('portal.home') }}" class="inline-flex text-white">@include('partials.brand')</a>
                <p class="mt-5 max-w-sm leading-relaxed">{{ __('portal.footer.about') }}</p>
            </div>
            <ul class="space-y-1">
                @foreach (['portal.methodology' => 'methodology', 'portal.data-protection' => 'data_protection', 'portal.open-data' => 'open_data'] as $footerRoute => $footerLabel)
                    <li><a class="inline-flex min-h-10 items-center font-semibold text-white/85 underline decoration-transparent decoration-2 underline-offset-4 transition-colors hover:text-white hover:decoration-gold-400" href="{{ route($footerRoute) }}">{{ __('portal.nav.'.$footerLabel) }}</a></li>
                @endforeach
            </ul>
            <p class="flex gap-3 self-start rounded-2xl border border-white/10 p-5 leading-relaxed"><span class="mt-2 size-2 shrink-0 rounded-full bg-gold-400" aria-hidden="true"></span>{{ __('portal.footer.privacy', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
