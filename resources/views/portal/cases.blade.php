@extends('portal.layout', ['title' => __('portal.cases.title'), 'description' => __('portal.cases.intro')])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <h1 class="portal-heading">{{ __('portal.cases.title') }}</h1>
        <p class="mt-3 max-w-3xl text-lg text-slate-600">{{ __('portal.cases.intro') }}</p>
        <livewire:portal.case-search />
    </div>
@endsection
