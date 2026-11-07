<div class="mt-10 grid items-start gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:gap-8" data-case-search>
    <form class="portal-card grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:sticky lg:top-24 lg:grid-cols-1" wire:submit.prevent role="search">
        <label class="text-sm sm:col-span-2 lg:col-span-1"><span class="portal-label">{{ __('portal.cases.search') }}</span>
            <span class="relative block">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
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

    <div class="min-w-0">
        <p class="text-[15px] font-semibold text-navy-900" aria-live="polite">{{ trans_choice('portal.cases.found', $cases->total(), ['count' => $cases->total()]) }}</p>

        <ul class="mt-4 grid gap-5 transition-opacity md:grid-cols-2" wire:loading.class="opacity-60">
            @foreach ($items as $item)
                <li class="portal-card portal-card-link group flex flex-col p-5 sm:p-6" data-public-case="{{ $item['ref'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="portal-chip bg-gold-100 text-navy-900">{{ $item['type'] }}</p>
                        <span class="portal-meta shrink-0 tabular-nums">{{ $item['year'] }}</span>
                    </div>
                    <h2 class="mt-4 text-lg leading-snug font-bold text-navy-900">
                        <a href="{{ route('portal.cases.show', ['publicRef' => $item['public_ref']]) }}" class="portal-stretched"><span class="block text-[13px] font-semibold tracking-wide text-slate-600 tabular-nums">{{ $item['ref'] }}<span class="sr-only"> · </span></span><span class="decoration-gold-400 decoration-2 underline-offset-4 group-hover:underline">{{ $item['court'] }}</span></a>
                    </h2>
                    <p class="mt-2 mb-5 line-clamp-2 text-[15px] leading-relaxed text-slate-700">{{ implode('; ', $item['offences']) ?: __('portal.cases.no_offence') }}</p>
                    <dl class="mt-auto grid grid-cols-2 gap-x-4 gap-y-3 border-t border-navy-900/10 pt-4 text-[15px]">
                        <div><dt class="portal-meta">{{ __('portal.cases.stage') }}</dt><dd class="mt-0.5 font-semibold text-navy-900">{{ $item['stage'] ?? '—' }}</dd></div>
                        <div><dt class="portal-meta">{{ __('portal.cases.outcome') }}</dt><dd class="mt-0.5 font-semibold text-navy-900">{{ $item['outcome'] ?? '—' }}</dd></div>
                        <div><dt class="portal-meta">{{ __('portal.cases.hearings') }}</dt><dd class="mt-0.5 font-semibold text-navy-900 tabular-nums">{{ $item['hearings'] }}</dd></div>
                        <div><dt class="portal-meta">{{ __('portal.cases.postponed') }}</dt><dd class="mt-0.5 font-semibold text-navy-900 tabular-nums">{{ $item['postponed'] }}</dd></div>
                    </dl>
                </li>
            @endforeach
        </ul>

        <div class="cw-pagination mt-8">{{ $cases->links() }}</div>
    </div>
</div>
