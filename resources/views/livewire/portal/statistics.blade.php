@php
    use App\Services\Anonymizer;
    $bar = fn (array $rows, string $color = '#4338ca', bool $horizontal = false, string $unit = '') => [
        'grid' => ['left' => 16, 'right' => 24, 'top' => 16, 'bottom' => 8, 'containLabel' => true],
        'tooltip' => ['trigger' => 'axis'],
        $horizontal ? 'yAxis' : 'xAxis' => ['type' => 'category', 'data' => array_map('strval', array_keys($rows)), 'inverse' => $horizontal, 'axisTick' => ['show' => false]],
        $horizontal ? 'xAxis' : 'yAxis' => ['type' => 'value', 'name' => $unit],
        'series' => [['type' => 'bar', 'data' => array_values($rows), 'itemStyle' => ['color' => $color, 'borderRadius' => 4]]],
    ];
    $pie = fn (array $rows) => [
        'tooltip' => ['trigger' => 'item'],
        'legend' => ['bottom' => 0, 'type' => 'scroll'],
        'color' => ['#4338ca', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#14b8a6'],
        'series' => [['type' => 'pie', 'radius' => ['40%', '68%'], 'center' => ['50%', '45%'], 'data' => collect($rows)->map(fn ($value, $label) => ['name' => $label, 'value' => $value])->values()->all()]],
    ];
    $courtRates = collect($byCourt)->mapWithKeys(fn ($row) => [$row['court'] => $row['rate']])->all();
    $durationRows = collect($durations)->mapWithKeys(fn ($row) => [$row['label'] => $row['median']])->all();
@endphp
<div class="space-y-8 py-10" data-portal-statistics>
    <form class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-4" wire:submit.prevent>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.year') }}</span>
            <select wire:model.live="year" class="portal-input">
                <option value="">{{ __('portal.filters.all_years') }}</option>
                @foreach ($years as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
            </select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.court') }}</span>
            <select wire:model.live="court" class="portal-input">
                <option value="">{{ __('portal.filters.all_courts') }}</option>
                @foreach ($courts as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
            </select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">{{ __('portal.filters.type') }}</span>
            <select wire:model.live="type" class="portal-input">
                <option value="">{{ __('portal.filters.all_types') }}</option>
                @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select></label>
        <div class="flex items-end"><button type="button" wire:click="resetFilters" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('portal.filters.reset') }}</button></div>
    </form>

    <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6" wire:loading.class="opacity-60">
        @foreach ([
            'cases' => [$headline['cases'], ''],
            'hearings' => [$headline['hearings'], ''],
            'postponement_rate' => [$headline['postponement_rate'], ' %'],
            'late_rate' => [$headline['late_rate'], ' %'],
            'public_rate' => [$headline['public_rate'], ' %'],
            'conviction_rate' => [$headline['conviction_rate'], ' %'],
        ] as $key => [$value, $suffix])
            <div class="rounded-2xl border border-slate-200 p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('portal.stats.'.$key) }}</dt>
                <dd class="mt-1 text-2xl font-bold text-slate-900" data-stat="{{ $key }}">{{ Anonymizer::display($value, $suffix) }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-6 lg:grid-cols-2" wire:loading.class="opacity-60">
        @include('portal.partials.chart', ['title' => __('portal.charts.hearings_by_year'), 'option' => $bar($byYear), 'rows' => $byYear])
        @include('portal.partials.chart', ['title' => __('portal.charts.cases_by_type'), 'option' => $pie($byType), 'rows' => $byType])
        @include('portal.partials.chart', ['title' => __('portal.charts.postponement_by_court'), 'option' => $bar($courtRates, '#f59e0b', true, '%'), 'rows' => $courtRates, 'suffix' => ' %'])
        @include('portal.partials.chart', ['title' => __('portal.charts.postponement_reasons'), 'option' => $bar($reasons, '#0ea5e9', true), 'rows' => $reasons])
        @include('portal.partials.chart', ['title' => __('portal.charts.verdicts'), 'option' => $pie($verdicts), 'rows' => $verdicts])
        @include('portal.partials.chart', ['title' => __('portal.charts.durations'), 'option' => $bar($durationRows, '#10b981', true, __('portal.days')), 'rows' => $durationRows])
    </div>

    <p class="text-sm text-slate-500">{{ __('portal.suppression_note', ['threshold' => Anonymizer::threshold()]) }}</p>
</div>
