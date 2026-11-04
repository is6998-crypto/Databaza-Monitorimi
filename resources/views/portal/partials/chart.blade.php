{{-- ECharts chart with an accessible data table. $option: ECharts option array. $rows: [label => value|null]. --}}
<figure class="rounded-2xl border border-slate-200 p-4">
    <figcaption class="mb-2 font-semibold text-slate-900">{{ $title }}</figcaption>
    <div class="h-72 w-full" wire:key="chart-{{ md5(json_encode($option)) }}" x-data="echart(@js($option))" wire:ignore role="img" aria-label="{{ $title }}"></div>
    <details class="mt-2 text-sm">
        <summary class="cursor-pointer text-slate-500">{{ __('portal.show_data') }}</summary>
        <table class="mt-2 w-full text-left">
            <tbody class="divide-y divide-slate-100">
                @foreach ($rows as $label => $value)
                    <tr><th class="py-1 pr-2 font-normal text-slate-600">{{ $label }}</th><td class="py-1 text-right font-medium">{{ \App\Services\Anonymizer::display($value, $suffix ?? '') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
