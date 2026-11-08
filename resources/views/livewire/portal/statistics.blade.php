@php
    use App\Services\Anonymizer;
    use App\Support\Brand;
    // The one trend (hearings per year): thin navy columns, the latest year in yellow and labelled, recessive axes.
    // Every comparison below is a ranked bar list in HTML (portal.partials.bar-list): no doughnuts, nothing clipped.
    // The offence categories (up to 28) get the full width, in two columns.
    $columns = function (array $rows): array {
        $values = array_values($rows);
        $last = count($values) - 1;

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
                    'itemStyle' => ['color' => $i === $last ? Brand::YELLOW : Brand::NAVY],
                    'label' => ['show' => $i === $last],
                ], $values, array_keys($values)),
            ]],
        ];
    };
    $courtRates = collect($byCourt)->mapWithKeys(fn ($row) => [$row['court'] => $row['rate']])->all();
    $durationRows = collect($durations)->mapWithKeys(fn ($row) => [$row['label'] => $row['median']])->all();
    $icons = [
        'cases' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        'hearings' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'postponement_rate' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'late_rate' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'public_rate' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'conviction_rate' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z',
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

        <dl class="grid grid-cols-2 gap-px border-t border-navy-900/10 bg-navy-900/10 transition-opacity sm:grid-cols-3 lg:grid-cols-6" wire:loading.class="opacity-60">
            @foreach ([
                'cases' => [$headline['cases'], ''],
                'hearings' => [$headline['hearings'], ''],
                'postponement_rate' => [$headline['postponement_rate'], ' %'],
                'late_rate' => [$headline['late_rate'], ' %'],
                'public_rate' => [$headline['public_rate'], ' %'],
                'conviction_rate' => [$headline['conviction_rate'], ' %'],
            ] as $key => [$value, $suffix])
                <div class="flex flex-col bg-white p-5 sm:p-6">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-gold-100 text-navy-900" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$key] }}"/></svg>
                    </span>
                    <dt class="order-2 mt-1 text-[15px] leading-snug font-medium text-slate-600">{{ __('portal.stats.'.$key) }}</dt>
                    <dd class="order-1 mt-4 text-[1.75rem] leading-none font-extrabold tracking-tight text-navy-900 tabular-nums sm:text-[2rem]" data-stat="{{ $key }}">{{ Anonymizer::display($value, $suffix) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="grid gap-6 lg:grid-cols-2" wire:loading.class="opacity-60">
        @include('portal.partials.chart', ['title' => __('portal.charts.hearings_by_year'), 'option' => $columns($byYear), 'rows' => $byYear, 'suffix' => ''])
        @include('portal.partials.bar-list', ['title' => __('portal.charts.cases_by_type'), 'rows' => $byType, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => false])
        @if ($byCategory !== [])
            <div class="lg:col-span-2">
                @include('portal.partials.bar-list', ['title' => __('portal.charts.cases_by_category'), 'rows' => $byCategory, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => true])
            </div>
        @endif
        @include('portal.partials.bar-list', ['title' => __('portal.charts.postponement_by_court'), 'rows' => $courtRates, 'suffix' => ' %', 'scale' => 'percent', 'ranked' => true, 'split' => false])
        @include('portal.partials.bar-list', ['title' => __('portal.charts.postponement_reasons'), 'rows' => $reasons, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => false])
        @include('portal.partials.bar-list', ['title' => __('portal.charts.verdicts'), 'rows' => $verdicts, 'suffix' => '', 'scale' => 'max', 'ranked' => true, 'split' => false])
        @include('portal.partials.bar-list', ['title' => __('portal.charts.durations'), 'rows' => $durationRows, 'suffix' => ' '.__('portal.days'), 'scale' => 'max', 'ranked' => false, 'split' => false])
    </div>

    <p class="flex gap-3 text-[15px] leading-relaxed text-slate-600"><svg class="mt-0.5 size-5 shrink-0 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>{{ __('portal.suppression_note', ['threshold' => Anonymizer::threshold()]) }}</p>
</div>
