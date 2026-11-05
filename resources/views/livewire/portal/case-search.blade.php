<div class="mt-8 grid items-start gap-6 lg:grid-cols-[19rem_minmax(0,1fr)]" data-case-search>
    <form class="portal-card grid gap-4 p-5 sm:grid-cols-2 lg:sticky lg:top-6 lg:grid-cols-1" wire:submit.prevent role="search">
        <label class="text-sm sm:col-span-2 lg:col-span-1"><span class="portal-label">{{ __('portal.cases.search') }}</span>
            <span class="relative block">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" wire:model.live.debounce.400ms="q" class="portal-input pl-9" placeholder="{{ __('portal.cases.search_placeholder') }}" maxlength="100">
            </span></label>
        <label class="text-sm"><span class="portal-label">{{ __('portal.filters.type') }}</span>
            <select wire:model.live="type" class="portal-input"><option value="">{{ __('portal.filters.all_types') }}</option>
                @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="portal-label">{{ __('portal.filters.court') }}</span>
            <select wire:model.live="court" class="portal-input"><option value="">{{ __('portal.filters.all_courts') }}</option>
                @foreach ($courts as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="portal-label">{{ __('portal.filters.category') }}</span>
            <select wire:model.live="category" class="portal-input"><option value="">{{ __('portal.filters.all') }}</option>
                @foreach ($categories as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="portal-label">{{ __('portal.filters.outcome') }}</span>
            <select wire:model.live="outcome" class="portal-input"><option value="">{{ __('portal.filters.all') }}</option>
                @foreach ($outcomes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="portal-label">{{ __('portal.filters.year') }}</span>
            <select wire:model.live="year" class="portal-input"><option value="">{{ __('portal.filters.all_years') }}</option>
                @foreach ($years as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
        <div class="flex items-end sm:col-span-2 lg:col-span-1"><button type="button" wire:click="resetFilters" class="portal-btn-secondary w-full">{{ __('portal.filters.reset') }}</button></div>
    </form>

    <div class="min-w-0 space-y-4">
        <p class="text-sm font-semibold text-slate-600" aria-live="polite">{{ trans_choice('portal.cases.found', $cases->total(), ['count' => $cases->total()]) }}</p>

        <ul class="grid gap-4 md:grid-cols-2" wire:loading.class="opacity-60">
            @foreach ($items as $item)
                <li class="portal-card flex flex-col p-5 transition hover:border-gold-400 hover:shadow-lg hover:shadow-navy-900/5" data-public-case="{{ $item['ref'] }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="portal-chip bg-gold-100 text-navy-900">{{ $item['type'] }}</p>
                        <span class="portal-chip bg-slate-100 text-slate-600">{{ $item['year'] }}</span>
                    </div>
                    <h2 class="mt-3 flex items-start gap-2 text-lg leading-snug font-bold text-navy-900">
                        <svg class="mt-1 size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <a href="{{ route('portal.cases.show', ['publicRef' => $item['public_ref']]) }}" class="decoration-gold-400 decoration-2 underline-offset-4 hover:underline">{{ $item['ref'] }} · {{ $item['court'] }}</a>
                    </h2>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ implode('; ', $item['offences']) ?: __('portal.cases.no_offence') }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-sm">
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('portal.cases.stage') }}</dt><dd class="mt-0.5 font-semibold text-navy-900">{{ $item['stage'] ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('portal.cases.outcome') }}</dt><dd class="mt-0.5 font-semibold text-navy-900">{{ $item['outcome'] ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('portal.cases.hearings') }}</dt><dd class="mt-0.5 font-semibold text-navy-900 tabular-nums">{{ $item['hearings'] }}</dd></div>
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('portal.cases.postponed') }}</dt><dd class="mt-0.5 font-semibold text-navy-900 tabular-nums">{{ $item['postponed'] }}</dd></div>
                    </dl>
                </li>
            @endforeach
        </ul>

        <div class="cw-pagination">{{ $cases->links() }}</div>
    </div>
</div>
