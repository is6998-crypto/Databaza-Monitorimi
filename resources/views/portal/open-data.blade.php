@extends('portal.layout', ['title' => __('portal.nav.open_data'), 'description' => __('portal.open_data.intro')])

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <h1 class="portal-heading">{{ __('portal.nav.open_data') }}</h1>
        <p class="mt-3 text-lg text-slate-600">{{ __('portal.open_data.intro') }}</p>
        <p class="mt-3 text-sm text-slate-500">{{ __('portal.suppression_note', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</p>
        <ul class="mt-8 space-y-3">
            @foreach ($datasets as $key => $label)
                <li class="portal-card flex flex-wrap items-center justify-between gap-4 p-5">
                    <div class="flex items-center gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gold-100 text-navy-900" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                        </span>
                        <div><p class="font-bold text-navy-900">{{ $label }}</p><p class="text-sm text-slate-500">CSV · UTF-8</p></div>
                    </div>
                    <a href="{{ route('portal.open-data.download', ['dataset' => $key]) }}" class="portal-btn-secondary" data-dataset="{{ $key }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        {{ __('portal.open_data.download') }}
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-sm text-slate-500">{{ __('portal.open_data.licence') }}</p>
    </div>
@endsection
