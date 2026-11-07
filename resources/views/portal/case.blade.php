@extends('portal.layout', ['title' => $case['ref'].' · '.$case['court'], 'description' => $case['type'].' — '.implode('; ', $case['offences'])])

@section('content')
    <div data-public-case-detail>
        <div class="relative overflow-hidden bg-navy-900 text-white">
            @include('portal.partials.frame-mark', ['class' => 'pointer-events-none absolute -right-16 -bottom-24 hidden size-80 text-white/[.03] lg:block'])
            <div class="portal-wrap relative pt-10 pb-12 sm:pt-12 sm:pb-16">
                <div class="max-w-4xl space-y-4" data-enter>
                    <a href="{{ route('portal.cases') }}" class="group inline-flex min-h-10 items-center gap-2 text-[15px] font-semibold text-white/80 transition-colors hover:text-white">
                        <svg class="size-4 transition-transform duration-150 group-hover:-translate-x-[3px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6 6-6-6 6-6"/></svg>
                        {{ __('portal.cases.back') }}
                    </a>
                    <p><span class="portal-chip bg-gold-400 text-navy-900">{{ $case['type'] }}</span></p>
                    <h1 class="portal-heading tabular-nums">{{ $case['ref'] }}</h1>
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-lg text-white/80">
                        <svg class="size-5 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>
                        {{ $case['court'] }}@if ($case['department']) · {{ $case['department'] }}@endif · {{ $case['year'] }}
                    </p>
                </div>
            </div>
        </div>

        <div class="portal-wrap mt-10 grid items-start gap-6 lg:grid-cols-3 lg:gap-8">
            <div class="space-y-6 lg:col-span-2">
                <section class="portal-card p-6 sm:p-8" aria-labelledby="case-offences">
                    <h2 id="case-offences" class="text-xl font-bold text-navy-900">{{ __('portal.cases.offences') }}</h2>
                    <ul class="mt-4 divide-y divide-navy-900/10 text-base text-slate-700">
                        @forelse ($case['offences'] as $offence)
                            <li class="flex gap-3 py-3 first:pt-0 last:pb-0"><span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-gold-500" aria-hidden="true"></span>{{ $offence }}</li>
                        @empty
                            <li>{{ __('portal.cases.no_offence') }}</li>
                        @endforelse
                    </ul>
                </section>

                <section class="portal-card p-6 sm:p-8" aria-labelledby="case-timeline">
                    <h2 id="case-timeline" class="text-xl font-bold text-navy-900">{{ __('portal.cases.timeline') }}</h2>
                    <ol class="mt-6">
                        @forelse ($case['timeline'] as $step)
                            <li class="relative flex gap-4 pb-7 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute top-8 bottom-0 left-[15px] w-0.5 bg-navy-100" aria-hidden="true"></span>
                                @endunless
                                @if ($loop->last)
                                    <span class="relative flex size-8 shrink-0 items-center justify-center rounded-full border-[3px] border-gold-400 bg-white" aria-hidden="true"><span class="size-2.5 rounded-full bg-gold-400"></span></span>
                                @else
                                    <span class="relative flex size-8 shrink-0 items-center justify-center rounded-full bg-navy-900 text-white" aria-hidden="true">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </span>
                                @endif
                                <div class="pt-1">
                                    <p class="font-bold text-navy-900">{{ $step['stage'] }}@if ($step['outcome']) — {{ $step['outcome'] }}@endif</p>
                                    <p class="portal-meta mt-0.5 tabular-nums">{{ $step['date'] ?? '—' }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="text-slate-600">—</li>
                        @endforelse
                    </ol>
                </section>

                <p class="flex gap-3 rounded-2xl bg-navy-50 p-4 text-[15px] leading-relaxed text-navy-900 ring-1 ring-navy-100">
                    <svg class="mt-0.5 size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                    {{ __('portal.cases.anonymized_note') }}
                </p>
            </div>

            <section class="portal-card overflow-hidden lg:sticky lg:top-24" aria-labelledby="case-hearings">
                <h2 id="case-hearings" class="p-6 pb-0 text-xl font-bold text-navy-900">{{ __('portal.cases.monitored_hearings') }}</h2>
                <dl class="mx-6 mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-navy-900/10 ring-1 ring-navy-900/10">
                    @foreach (['total', 'postponed', 'late', 'public'] as $key)
                        <div class="flex flex-col bg-white p-4"><dt class="portal-meta order-2 mt-1">{{ __('portal.cases.hearing_'.$key) }}</dt><dd class="order-1 text-3xl leading-none font-extrabold text-navy-900 tabular-nums">{{ $case['hearing_summary'][$key] }}</dd></div>
                    @endforeach
                </dl>
                @if ($case['hearing_summary']['first'])
                    <p class="mt-5 flex gap-2 border-t border-navy-900/10 bg-canvas px-6 py-4 text-[15px] text-slate-700">
                        <svg class="mt-0.5 size-4 shrink-0 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        {{ __('portal.cases.monitored_between', ['first' => $case['hearing_summary']['first'], 'last' => $case['hearing_summary']['last']]) }}
                    </p>
                @else
                    <div class="h-6" aria-hidden="true"></div>
                @endif
            </section>
        </div>

    </div>
@endsection
