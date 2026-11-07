@extends('portal.layout', ['title' => '', 'description' => __('portal.meta_description')])

@push('head')
    {{-- The page's one dealt moment (pinned-chapters, the methodology steps): the kit's loader, unchanged in public/assets/moments. --}}
    <script>document.documentElement.classList.add('mo-js');setTimeout(()=>document.documentElement.classList.add('mo-safe'),2500)</script>
    <link rel="modulepreload" href="{{ asset('assets/moments/moments.js') }}">
    <link rel="modulepreload" href="{{ asset('assets/moments/lib.js') }}">
    <link rel="modulepreload" href="{{ asset('assets/moments/pinned-chapters/moment.js') }}">
    <script type="module" src="{{ asset('assets/moments/moments.js') }}"></script>
@endpush

@section('content')
    {{-- Hero (dealt: split). The words at the left; at the right the real product: the latest verified cases as the case search shows them. --}}
    {{-- It starts under the sticky header (pulled up by the header's height) and fills the first screen. --}}
    <section class="hero relative -mt-16 flex min-h-svh flex-col overflow-hidden bg-navy-900 pt-16 text-white lg:-mt-[72px] lg:pt-[72px]" data-enter aria-labelledby="home-title">
        <div class="wrap portal-wrap flex flex-1 flex-col">
        <div class="hero-text flex flex-1 flex-col gap-8 pt-8 pb-6 sm:pt-12 sm:pb-12 lg:grid lg:grid-cols-12 lg:gap-6 lg:pt-12 lg:pb-16">
            <div class="flex flex-col lg:col-span-6 lg:pr-6">
                <p class="portal-kicker" data-rise>{{ __('portal.home.kicker') }}</p>
                <h1 id="home-title" class="mt-5 text-[2.125rem] leading-[1.06] font-extrabold tracking-tight text-balance sm:text-5xl xl:text-[3.5rem]" data-rise style="--d: 60ms">{{ __('portal.home.title') }}</h1>
                <p class="portal-lead mt-6 text-white/80 sm:text-xl" data-rise style="--d: 200ms">{{ __('portal.home.intro') }}</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row" data-rise style="--d: 260ms">
                    <a href="{{ route('portal.cases') }}" class="portal-btn-primary">
                        {{ __('portal.home.cta_cases') }}
                        <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('portal.open-data') }}" class="portal-btn-secondary">{{ __('portal.home.cta_data') }}</a>
                </div>
            </div>

            <div class="hero__media frame relative min-h-[12rem] flex-1 overflow-hidden rounded-2xl bg-navy-200 lg:col-span-6 lg:col-start-7 lg:h-[calc(100svh-72px-7rem)] lg:max-h-[44.75rem] lg:min-h-0" data-visual data-rise="media">
                {{-- The window runs off the frame's right and lower edges: a glimpse of the case search, at true size. --}}
                <div class="ui absolute top-8 bottom-0 left-8 w-[34rem] max-w-none overflow-hidden rounded-t-xl bg-white text-slate-700 shadow-[0_32px_64px_-24px_rgb(0_0_0_/_0.5)] sm:top-10 sm:left-10" data-ui="case-search">
                    <div class="flex items-center gap-3 border-b border-navy-900/10 px-5 py-3.5">
                        @include('partials.logo-mark', ['class' => 'size-5 shrink-0'])
                        <p class="text-[15px] font-bold text-navy-900">{{ __('portal.cases.title') }}</p>
                    </div>
                    <div class="px-5 pt-4">
                        {{-- A drawing of the search field (the real one is on the case search page). --}}
                        <div class="flex min-h-11 items-center gap-2.5 rounded-xl border border-slate-300 px-3 text-[15px] text-slate-500" aria-hidden="true">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            {{ __('portal.cases.search_placeholder') }}
                        </div>
                    </div>
                    <ul class="hero-cases divide-y divide-navy-900/10 px-5 pt-2">
                        @foreach ($recentCases as $item)
                            <li class="py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="portal-chip bg-gold-100 text-navy-900">{{ $item['type'] }}</span>
                                    <span class="portal-meta tabular-nums">{{ $item['year'] }}</span>
                                </div>
                                <a href="{{ route('portal.cases.show', ['publicRef' => $item['public_ref']]) }}" class="group mt-2 block rounded font-bold text-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                                    <span class="block text-[13px] font-semibold tracking-wide text-slate-600 tabular-nums">{{ $item['ref'] }}</span>
                                    <span class="decoration-gold-400 decoration-2 underline-offset-4 group-hover:underline">{{ $item['court'] }}</span>
                                </a>
                                <p class="mt-1 text-[15px] text-slate-600">{{ __('portal.cases.stage') }}: <span class="font-semibold text-navy-900">{{ $item['stage'] ?? '—' }}</span></p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($lastUpdate)
                    {{-- The one float card: a live fact. --}}
                    <div class="absolute bottom-6 left-14 hidden w-[18.75rem] rounded-xl sm:left-16 md:block border border-navy-900/10 bg-white p-4 text-navy-900">
                        <p class="flex items-center gap-2 text-[13px] font-semibold text-slate-600"><span class="portal-breathe size-2 rounded-full bg-gold-500" aria-hidden="true"></span>{{ __('portal.home.updated') }}</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums">{{ $lastUpdate }}</p>
                    </div>
                @endif
            </div>
        </div>
        </div>
    </section>

    {{-- Statistics: the filters, the six headline numbers and the charts. --}}
    <section class="py-20 lg:min-h-[200svh] lg:pt-16 lg:pb-12" aria-labelledby="statistics-title">
        <div class="wrap portal-wrap">
            <h2 id="statistics-title" class="portal-section-title">{{ __('portal.home.statistics') }}</h2>
            <div class="mt-6">
                <livewire:portal.statistics />
            </div>
        </div>
    </section>

    @include('portal.partials.how-it-works')

    @if ($publications->isNotEmpty())
        <section class="py-20 sm:min-h-svh sm:py-24" aria-labelledby="reports-title">
        <div class="wrap portal-wrap">
            <h2 id="reports-title" class="portal-section-title">{{ __('portal.home.latest_reports') }}</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($publications as $publication)
                    @include('portal.partials.publication-card', ['publication' => $publication])
                @endforeach
            </div>
            <a href="{{ route('portal.publications') }}" class="portal-btn-secondary mt-10">
                {{ __('portal.home.all_reports') }}
                <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
        </section>
    @endif
@endsection

@section('close')
    {{-- The close (dealt: close-type): the tagline set large with the main action, word for word as in the hero, and what the programme is beside it. --}}
    <div class="lg:col-span-6">
        <p class="text-4xl leading-[1.05] font-extrabold tracking-tight text-balance text-white sm:text-6xl xl:text-7xl">{{ __('portal.tagline') }}</p>
        <a href="{{ route('portal.cases') }}" class="portal-btn-primary mt-10">
            {{ __('portal.home.cta_cases') }}
            <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>
    </div>
    <p class="text-xl leading-relaxed text-white/80 sm:text-2xl lg:col-span-6 lg:col-start-7">{{ __('portal.footer.about') }}</p>
@endsection
