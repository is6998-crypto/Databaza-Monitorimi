@extends('portal.layout', ['title' => __('portal.nav.publications'), 'description' => __('portal.publications.intro')])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <h1 class="portal-heading">{{ __('portal.nav.publications') }}</h1>
        <p class="mt-3 max-w-3xl text-lg text-slate-600">{{ __('portal.publications.intro') }}</p>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @forelse ($publications as $publication)
                @include('portal.partials.publication-card', ['publication' => $publication])
            @empty
                <p class="portal-card p-6 text-slate-500 md:col-span-3">{{ __('portal.publications.empty') }}</p>
            @endforelse
        </div>
        <div class="cw-pagination mt-8">{{ $publications->links() }}</div>
    </div>
@endsection
