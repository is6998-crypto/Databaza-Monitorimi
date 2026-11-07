@extends('portal.layout', ['title' => __('portal.nav.publications'), 'description' => __('portal.publications.intro')])

@section('content')
    @include('portal.partials.page-header', ['title' => __('portal.nav.publications'), 'intro' => __('portal.publications.intro')])
    <div class="wrap portal-wrap mt-10">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($publications as $publication)
                @include('portal.partials.publication-card', ['publication' => $publication])
            @empty
                <p class="card portal-card p-6 text-slate-600 md:col-span-2 lg:col-span-3">{{ __('portal.publications.empty') }}</p>
            @endforelse
        </div>
        <div class="cw-pagination mt-10">{{ $publications->links() }}</div>
    </div>
@endsection
