@extends('portal.layout', ['title' => '', 'description' => __('portal.meta_description')])

@section('content')
    <section class="relative overflow-hidden bg-navy-900 text-white">
        <div class="pointer-events-none absolute top-0 right-0 size-12 bg-gold-400 [clip-path:polygon(0_0,100%_0,100%_100%)] sm:size-28" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-7xl px-4 pt-12 pb-24 sm:px-6 sm:pt-16 sm:pb-28">
            <p class="flex items-center gap-3 text-sm font-bold tracking-[0.14em] text-gold-400 uppercase before:h-5 before:w-1 before:rounded-full before:bg-gold-400 before:content-['']">{{ __('portal.home.kicker') }}</p>
            <h1 class="mt-5 max-w-4xl text-4xl leading-[1.08] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">{{ __('portal.home.title') }}</h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/80">{{ __('portal.home.intro') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('portal.cases') }}" class="portal-btn-primary">
                    {{ __('portal.home.cta_cases') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
                <a href="{{ route('portal.open-data') }}" class="inline-flex items-center justify-center rounded-xl border border-white/30 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">{{ __('portal.home.cta_data') }}</a>
            </div>
        </div>
    </section>

    <section class="relative mx-auto -mt-14 max-w-7xl px-4 sm:px-6" aria-labelledby="statistics-title">
        <h2 id="statistics-title" class="sr-only">{{ __('portal.home.statistics') }}</h2>
        <livewire:portal.statistics />
    </section>

    @if ($publications->isNotEmpty())
        <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl font-extrabold tracking-tight text-navy-900">{{ __('portal.home.latest_reports') }}</h2>
                <a href="{{ route('portal.publications') }}" class="portal-link text-sm">{{ __('portal.home.all_reports') }} →</a>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-3">
                @foreach ($publications as $publication)
                    @include('portal.partials.publication-card', ['publication' => $publication])
                @endforeach
            </div>
        </section>
    @endif
@endsection
