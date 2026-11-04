@extends('portal.layout', ['title' => '', 'description' => __('portal.meta_description')])

@section('content')
    <section class="bg-gradient-to-b from-indigo-50 to-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">{{ __('portal.home.kicker') }}</p>
            <h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('portal.home.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg text-slate-600">{{ __('portal.home.intro') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('portal.cases') }}" class="rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-600">{{ __('portal.home.cta_cases') }}</a>
                <a href="{{ route('portal.open-data') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ __('portal.home.cta_data') }}</a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6" aria-labelledby="statistics-title">
        <h2 id="statistics-title" class="sr-only">{{ __('portal.home.statistics') }}</h2>
        <livewire:portal.statistics />
    </section>

    @if ($publications->isNotEmpty())
        <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold text-slate-900">{{ __('portal.home.latest_reports') }}</h2>
                <a href="{{ route('portal.publications') }}" class="text-sm font-semibold text-indigo-700 hover:underline">{{ __('portal.home.all_reports') }} →</a>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($publications as $publication)
                    @include('portal.partials.publication-card', ['publication' => $publication])
                @endforeach
            </div>
        </section>
    @endif
@endsection
