<div class="mt-8 space-y-6" data-case-search>
    <form class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-6" wire:submit.prevent role="search">
        <label class="text-sm sm:col-span-2"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.cases.search') }}</span>
            <input type="search" wire:model.live.debounce.400ms="q" class="portal-input" placeholder="{{ __('portal.cases.search_placeholder') }}" maxlength="100"></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.type') }}</span>
            <select wire:model.live="type" class="portal-input"><option value="">{{ __('portal.filters.all_types') }}</option>
                @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.court') }}</span>
            <select wire:model.live="court" class="portal-input"><option value="">{{ __('portal.filters.all_courts') }}</option>
                @foreach ($courts as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.category') }}</span>
            <select wire:model.live="category" class="portal-input"><option value="">{{ __('portal.filters.all') }}</option>
                @foreach ($categories as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.outcome') }}</span>
            <select wire:model.live="outcome" class="portal-input"><option value="">{{ __('portal.filters.all') }}</option>
                @foreach ($outcomes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.year') }}</span>
            <select wire:model.live="year" class="portal-input"><option value="">{{ __('portal.filters.all_years') }}</option>
                @foreach ($years as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
        <div class="flex items-end"><button type="button" wire:click="resetFilters" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('portal.filters.reset') }}</button></div>
    </form>

    <p class="text-sm text-slate-600" aria-live="polite">{{ trans_choice('portal.cases.found', $cases->total(), ['count' => $cases->total()]) }}</p>

    <ul class="grid gap-4 md:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-60">
        @foreach ($items as $item)
            <li class="flex flex-col rounded-2xl border border-slate-200 p-5" data-public-case="{{ $item['ref'] }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $item['type'] }}</p>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $item['year'] }}</span>
                </div>
                <h2 class="mt-2 font-semibold text-slate-900"><a href="{{ route('portal.cases.show', ['publicRef' => $item['public_ref']]) }}" class="hover:underline">{{ $item['ref'] }} · {{ $item['court'] }}</a></h2>
                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ implode('; ', $item['offences']) ?: __('portal.cases.no_offence') }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('portal.cases.stage') }}</dt><dd class="font-medium">{{ $item['stage'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('portal.cases.outcome') }}</dt><dd class="font-medium">{{ $item['outcome'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('portal.cases.hearings') }}</dt><dd class="font-medium">{{ $item['hearings'] }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('portal.cases.postponed') }}</dt><dd class="font-medium">{{ $item['postponed'] }}</dd></div>
                </dl>
            </li>
        @endforeach
    </ul>

    <div>{{ $cases->links() }}</div>
</div>
