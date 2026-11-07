@extends('portal.layout', ['title' => '', 'description' => __('portal.meta_description')])

@section('content')
    <section class="relative overflow-hidden bg-navy-900 text-white" aria-labelledby="home-title">
        @include('portal.partials.frame-mark', ['class' => 'pointer-events-none absolute top-1/2 -right-24 hidden size-[34rem] -translate-y-[58%] text-white/[.025] lg:block'])
        <div class="portal-wrap relative pt-14 pb-32 sm:pt-20 sm:pb-36">
            <div class="max-w-4xl" data-enter>
                <p class="portal-kicker">{{ __('portal.home.kicker') }}</p>
                <h1 id="home-title" class="mt-5 text-[2.5rem] leading-[1.05] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-[4rem]">{{ __('portal.home.title') }}</h1>
                <p class="portal-lead mt-6 text-white/80 sm:text-xl">{{ __('portal.home.intro') }}</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('portal.cases') }}" class="portal-btn-primary">
                        {{ __('portal.home.cta_cases') }}
                        <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('portal.open-data') }}" class="portal-btn-inverse">{{ __('portal.home.cta_data') }}</a>
                </div>
            </div>
        </div>
    </section>

    <section class="portal-wrap relative -mt-20" aria-labelledby="statistics-title">
        <h2 id="statistics-title" class="sr-only">{{ __('portal.home.statistics') }}</h2>
        <livewire:portal.statistics />
    </section>

    @if ($publications->isNotEmpty())
        <section class="portal-wrap mt-20" aria-labelledby="reports-title">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="reports-title" class="portal-section-title">{{ __('portal.home.latest_reports') }}</h2>
                <a href="{{ route('portal.publications') }}" class="group inline-flex min-h-10 items-center gap-2 text-[15px] font-bold text-navy-900 underline decoration-gold-400 decoration-2 underline-offset-4 hover:decoration-navy-900">
                    {{ __('portal.home.all_reports') }}
                    <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($publications as $publication)
                    @include('portal.partials.publication-card', ['publication' => $publication])
                @endforeach
            </div>
        </section>
    @endif
@endsection
