@php
    use App\Services\Anonymizer;
    use App\Support\Brand;
    // The public overview of the monitored cases (App\Services\Portal\PortalOverview): how many, and by year, court,
    // case type and offence category. No finding of the monitoring is shown here.
    // The one trend (cases per year): thin navy columns, the chosen year (else the latest) in yellow and labelled.
    // Every comparison below is a ranked bar list in HTML (portal.partials.bar-list): no doughnuts, nothing clipped.
    // The long lists (courts, types, categories) get the full width, in two columns.
    $columns = function (array $rows, ?string $highlight): array {
        $values = array_values($rows);
        $keys = array_map('strval', array_keys($rows));
        $marked = $highlight !== null && in_array($highlight, $keys, true) ? array_search($highlight, $keys, true) : count($values) - 1;

        return [
            'grid' => ['left' => 4, 'right' => 4, 'top' => 32, 'bottom' => 4, 'containLabel' => true],
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => ['type' => 'shadow', 'shadowStyle' => ['color' => 'rgba(12,42,66,0.04)']],
                'backgroundColor' => '#ffffff', 'borderColor' => '#e2e5ea', 'borderWidth' => 1, 'padding' => [8, 12],
                'textStyle' => ['color' => '#0c2a42', 'fontSize' => 13],
                'extraCssText' => 'border-radius:10px;box-shadow:0 8px 24px -12px rgba(12,42,66,.3);',
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => array_map('strval', array_keys($rows)),
                'axisTick' => ['show' => false],
                'axisLine' => ['lineStyle' => ['color' => '#cbd1d9']],
                'axisLabel' => ['color' => '#4b5768', 'fontSize' => 13, 'margin' => 12],
            ],
            'yAxis' => [
                'type' => 'value',
                'splitNumber' => 4,
                'axisLabel' => ['color' => '#677386', 'fontSize' => 13],
                'splitLine' => ['lineStyle' => ['color' => '#eceef1']],
            ],
            'series' => [[
                'type' => 'bar',
                'barMaxWidth' => 28,
                'itemStyle' => ['borderRadius' => [4, 4, 0, 0]],
                'label' => ['position' => 'top', 'distance' => 8, 'color' => '#0c2a42', 'fontSize' => 13, 'fontWeight' => 700],
                'emphasis' => ['disabled' => true],
                'data' => array_map(fn ($value, int $i): array => [
                    'value' => $value,
                    'itemStyle' => ['color' => $i === $marked ? Brand::YELLOW : Brand::NAVY],
                    'label' => ['show' => $i === $marked],
                ], $values, array_keys($values)),
            ]],
        ];
    };
    $icons = [
        'cases' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        'hearings' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'courts' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z',
    ];
@endphp
<div class="space-y-6" data-portal-statistics>
    <div class="card portal-card overflow-hidden shadow-[0_24px_48px_-24px_rgb(12_42_66_/_0.35)]">
        <form class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto]" wire:submit.prevent>
            <label class="text-sm"><span class="portal-label">{{ __('portal.filters.year') }}</span>
                <select wire:model.live="year" class="portal-input">
                    <option value="">{{ __('portal.filters.all_years') }}</option>
                    @foreach ($years as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                </select></label>
            <label class="text-sm"><span class="portal-label">{{ __('portal.filters.court') }}</span>
                <select wire:model.live="court" class="portal-input">
                    <option value="">{{ __('portal.filters.all_courts') }}</option>
                    @foreach ($courts as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                </select></label>
            <label class="text-sm"><span class="portal-label">{{ __('portal.filters.type') }}</span>
                <select wire:model.live="type" class="portal-input">
                    <option value="">{{ __('portal.filters.all_types') }}</option>
                    @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></label>
            <div class="flex items-end"><button type="button" wire:click="resetFilters" class="portal-btn-secondary w-full lg:w-auto lg:px-6">{{ __('portal.filters.reset') }}</button></div>
        </form>

        <dl class="grid gap-px border-t border-navy-900/10 bg-navy-900/10 transition-opacity sm:grid-cols-3" wire:loading.class="opacity-60">
            @foreach ([
                'cases' => [$headline['cases'], ''],
                'hearings' => [$headline['hearings'], ''],
                'courts' => [$headline['courts'], ''],
            ] as $key => [$value, $suffix])
                <div class="flex flex-col bg-white p-5 sm:p-6">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-gold-100 text-navy-900" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$key] }}"/></svg>
                    </span>
                    <dt class="order-2 mt-1 text-[15px] leading-snug font-medium text-slate-600">{{ __('portal.stats.'.$key) }}</dt>
                    <dd class="order-1 mt-4 text-[2rem] leading-none font-extrabold tracking-tight text-navy-900 tabular-nums sm:text-[2.5rem]" data-stat="{{ $key }}">{{ Anonymizer::display($value, $suffix) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="grid gap-6 lg:grid-cols-2" wire:loading.class="opacity-60">
        @include('portal.partials.chart', ['title' => __('portal.charts.cases_by_year'), 'option' => $columns($byYear, $selectedYear), 'rows' => $byYear, 'suffix' => ''])
        @include('portal.partials.bar-list', ['title' => __('portal.charts.cases_by_type'), 'rows' => $byType, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => false])
        @if ($byCourt !== [])
            <div class="lg:col-span-2">
                @include('portal.partials.bar-list', ['title' => __('portal.charts.cases_by_court'), 'rows' => $byCourt, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => true])
            </div>
        @endif
        @if ($byCategory !== [])
            <div class="lg:col-span-2">
                @include('portal.partials.bar-list', ['title' => __('portal.charts.cases_by_category'), 'rows' => $byCategory, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => true])
            </div>
        @endif
    </div>

    <p class="flex gap-3 text-[15px] leading-relaxed text-slate-600"><svg class="mt-0.5 size-5 shrink-0 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>{{ __('portal.suppression_note', ['threshold' => Anonymizer::threshold()]) }}</p>
</div>
