@extends('portal.layout', ['title' => __('portal.nav.open_data'), 'description' => __('portal.open_data.intro')])

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('portal.nav.open_data') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('portal.open_data.intro') }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ __('portal.suppression_note', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</p>
        <ul class="mt-8 divide-y divide-slate-200 rounded-2xl border border-slate-200">
            @foreach ($datasets as $key => $label)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div><p class="font-medium text-slate-900">{{ $label }}</p><p class="text-sm text-slate-500">CSV · UTF-8</p></div>
                    <a href="{{ route('portal.open-data.download', ['dataset' => $key]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50" data-dataset="{{ $key }}">{{ __('portal.open_data.download') }}</a>
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-sm text-slate-500">{{ __('portal.open_data.licence') }}</p>
    </div>
@endsection
