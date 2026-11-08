{{--
    Chart builder: the choices (what to count, split by, compare by, chart type), the filters, the chart, and the
    same numbers as a table with a CSV download. Cells resting on fewer than 5 cases or hearings are marked: the
    data-use agreement forbids publishing them.
--}}
@php
    use App\Filament\Research\Pages\Charts;
    use App\Filament\Research\Widgets\ResearchChart;
    use App\Services\Research\ChartBuilder;
    $reasonAllowed = $measure === 'postponed';
    $hasSmall = collect($result['series'])->flatMap(fn ($series) => $series['bases'])->contains(fn ($base) => ChartBuilder::isSmall($base));
@endphp
<x-filament-panels::page>
    <div class="space-y-6" data-research-charts>
        <x-filament::section :heading="__('research.charts.build')" :description="__('research.charts.intro')">
            <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['measure', __('research.charts.fields.measure'), $measures, null, []],
                    ['dimension', __('research.charts.fields.dimension'), $dimensions, null, $reasonAllowed ? [] : ['reason']],
                    ['compare', __('research.charts.fields.compare'), collect($dimensions)->except($dimension)->all(), __('research.charts.no_compare'), $reasonAllowed ? [] : ['reason']],
                    ['chartType', __('research.charts.fields.chart_type'), collect(['auto', 'bar', 'stacked', 'line'])->mapWithKeys(fn ($key) => [$key => __('research.charts.types.'.$key)])->all(), null, $compare ? [] : ['stacked']],
                ] as [$model, $label, $options, $empty, $disabled])
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="{{ $model }}">
                                @if ($empty)<option value="">{{ $empty }}</option>@endif
                                @foreach ($options as $value => $text)
                                    <option value="{{ $value }}" @disabled(in_array($value, $disabled, true))>{{ $text }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 border-t border-gray-950/5 pt-5 dark:border-white/10">
                <p class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">{{ __('research.charts.filters') }}</p>
                <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2 xl:grid-cols-[repeat(5,minmax(0,1fr))_auto]">
                    @foreach ([
                        ['yearFrom', __('research.charts.fields.year_from'), array_combine($years, $years), __('research.charts.any_year')],
                        ['yearTo', __('research.charts.fields.year_to'), array_combine($years, $years), __('research.charts.any_year')],
                        ['court', __('research.charts.fields.court'), $courts, __('research.charts.all_courts')],
                        ['caseType', __('research.charts.fields.case_type'), $types, __('research.charts.all_types')],
                        ['category', __('research.charts.fields.category'), $categories, __('research.charts.all_categories')],
                    ] as [$model, $label, $options, $empty])
                        <label class="block min-w-0">
                            <span class="mb-1.5 block truncate text-sm font-medium text-gray-950 dark:text-white" title="{{ $label }}">{{ $label }}</span>
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model.live="{{ $model }}">
                                    <option value="">{{ $empty }}</option>
                                    @foreach ($options as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </label>
                    @endforeach
                    <div class="flex items-end">
                        <x-filament::button color="gray" wire:click="resetChart" class="w-full xl:w-auto">{{ __('research.charts.reset') }}</x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ $measures[$result['measure']] }}
                <span class="font-normal text-gray-500 dark:text-gray-400">· {{ __('research.charts.by', ['dimension' => mb_strtolower($dimensions[$result['dimension']])]) }}@if ($result['compare']) {{ __('research.charts.and_by', ['dimension' => mb_strtolower($dimensions[$result['compare']])]) }}@endif</span>
            </x-slot>
            <x-slot name="description">
                {{ trans_choice('research.charts.based_on_'.($result['measure'] === 'cases' ? 'cases' : 'hearings'), $result['total'], ['count' => number_format($result['total'], 0, ',', '.')]) }}
            </x-slot>
            <x-slot name="afterHeader">
                @if ($result['labels'] !== [])
                    <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="download">{{ __('research.charts.download') }}</x-filament::button>
                @endif
            </x-slot>

            <div wire:loading.class="opacity-60" class="transition-opacity">
                @if ($result['labels'] === [])
                    <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400" data-chart-empty>{{ __('research.charts.empty') }}</p>
                @else
                    @livewire(ResearchChart::class, ['result' => $result, 'chartType' => $type], key('chart-'.md5(json_encode([$result, $type]))))

                    @if ($result['truncated'])
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('research.charts.truncated', ['count' => ChartBuilder::MAX_SERIES]) }}</p>
                    @endif

                    <div class="mt-6 overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                        <table class="w-full text-left text-sm" data-chart-table>
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 font-semibold text-gray-950 dark:text-white">{{ $dimensions[$result['dimension']] }}</th>
                                    @foreach ($result['series'] as $series)
                                        <th scope="col" class="px-4 py-2.5 text-right font-semibold text-gray-950 dark:text-white">{{ $series['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-950/5 dark:divide-white/10">
                                @foreach ($result['labels'] as $i => $label)
                                    <tr>
                                        <th scope="row" class="px-4 py-2 font-normal text-gray-700 dark:text-gray-300">{{ $label }}</th>
                                        @foreach ($result['series'] as $series)
                                            @php $small = ChartBuilder::isSmall($series['bases'][$i]); @endphp
                                            <td @class(['px-4 py-2 text-right tabular-nums', 'font-semibold text-gray-950 dark:text-white' => ! $small, 'text-gray-400 dark:text-gray-500' => $small]) @if ($small) data-small-cell @endif>
                                                {{ Charts::format($series['values'][$i], $result['measure']) }}@if ($small)<sup aria-label="{{ __('research.charts.small_cell') }}">*</sup>@endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <ul class="mt-4 space-y-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    @if ($hasSmall)
                        <li>* {{ __('research.charts.small_note', ['threshold' => \App\Services\Anonymizer::threshold()]) }}</li>
                    @endif
                    @if (in_array('category', [$result['dimension'], $result['compare']], true))
                        <li>{{ __('research.charts.category_note') }}</li>
                    @endif
                    <li>{{ __('research.charts.verified_note') }}</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
