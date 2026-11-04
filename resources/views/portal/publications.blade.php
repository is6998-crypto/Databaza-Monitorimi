@extends('portal.layout', ['title' => __('portal.nav.publications'), 'description' => __('portal.publications.intro')])

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('portal.nav.publications') }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">{{ __('portal.publications.intro') }}</p>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @forelse ($publications as $publication)
                @include('portal.partials.publication-card', ['publication' => $publication])
            @empty
                <p class="text-slate-500">{{ __('portal.publications.empty') }}</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $publications->links() }}</div>
    </div>
@endsection
