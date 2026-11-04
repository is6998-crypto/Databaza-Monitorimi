@extends('portal.layout', ['title' => __('portal.cases.title'), 'description' => __('portal.cases.intro')])

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('portal.cases.title') }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">{{ __('portal.cases.intro') }}</p>
        <livewire:portal.case-search />
    </div>
@endsection
