{{-- ECharts chart with an accessible data table. $option: ECharts option array. $rows: [label => value|null]. --}}
<figure class="portal-card p-5">
    <figcaption class="mb-2 text-lg font-bold text-navy-900">{{ $title }}</figcaption>
    <div class="h-72 w-full" wire:key="chart-{{ md5(json_encode($option)) }}" x-data="echart(@js($option))" wire:ignore role="img" aria-label="{{ $title }}"></div>
    <details class="mt-3 text-sm">
        <summary class="cursor-pointer font-semibold text-slate-500 hover:text-navy-900">{{ __('portal.show_data') }}</summary>
        <table class="mt-2 w-full text-left">
            <tbody class="divide-y divide-slate-100">
                @foreach ($rows as $label => $value)
                    <tr><th class="py-1.5 pr-2 font-normal text-slate-600">{{ $label }}</th><td class="py-1.5 text-right font-semibold text-navy-900 tabular-nums">{{ \App\Services\Anonymizer::display($value, $suffix ?? '') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
