@extends('portal.layout', ['title' => $case['ref'].' · '.$case['court'], 'description' => $case['type'].' — '.implode('; ', $case['offences'])])

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6" data-public-case-detail>
        <a href="{{ route('portal.cases') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← {{ __('portal.cases.back') }}</a>
        <p class="mt-6 text-sm font-semibold uppercase tracking-wide text-indigo-700">{{ $case['type'] }}</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $case['ref'] }}</h1>
        <p class="mt-2 text-slate-600">{{ $case['court'] }}@if ($case['department']) · {{ $case['department'] }}@endif · {{ $case['year'] }}</p>

        <section class="mt-8">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('portal.cases.offences') }}</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-700">
                @forelse ($case['offences'] as $offence)<li>{{ $offence }}</li>@empty<li>{{ __('portal.cases.no_offence') }}</li>@endforelse
            </ul>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('portal.cases.timeline') }}</h2>
            <ol class="mt-4 space-y-3 border-l-2 border-indigo-200 pl-5">
                @foreach ($case['timeline'] as $step)
                    <li>
                        <p class="font-medium text-slate-900">{{ $step['stage'] }}@if ($step['outcome']) — {{ $step['outcome'] }}@endif</p>
                        <p class="text-sm text-slate-500">{{ $step['date'] ?? '—' }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('portal.cases.monitored_hearings') }}</h2>
            <dl class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach (['total', 'postponed', 'late', 'public'] as $key)
                    <div class="rounded-xl border border-slate-200 p-3"><dt class="text-xs text-slate-500">{{ __('portal.cases.hearing_'.$key) }}</dt><dd class="text-xl font-bold">{{ $case['hearing_summary'][$key] }}</dd></div>
                @endforeach
            </dl>
            @if ($case['hearing_summary']['first'])
                <p class="mt-2 text-sm text-slate-500">{{ __('portal.cases.monitored_between', ['first' => $case['hearing_summary']['first'], 'last' => $case['hearing_summary']['last']]) }}</p>
            @endif
        </section>

        <p class="mt-10 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{{ __('portal.cases.anonymized_note') }}</p>
    </div>
@endsection
