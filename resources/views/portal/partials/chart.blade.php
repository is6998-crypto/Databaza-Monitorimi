{{-- ECharts chart with an accessible data table. $option: ECharts option array. $rows: [label => value|null]. $tall: more height on phones (a doughnut with its legend). --}}
<figure class="portal-card flex flex-col p-5 sm:p-6">
    <figcaption class="text-lg leading-snug font-bold text-navy-900">{{ $title }}</figcaption>
    <div @class(['mt-4 w-full', 'h-96 sm:h-80' => $tall ?? false, 'h-80' => ! ($tall ?? false)]) wire:key="chart-{{ md5(json_encode($option)) }}" x-data="echart(@js($option))" wire:ignore role="img" aria-label="{{ $title }}"></div>
    <details class="group mt-4 border-t border-navy-900/10 pt-3 text-[15px]">
        <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-lg font-semibold text-slate-600 transition-colors hover:text-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400 [&::-webkit-details-marker]:hidden">
            <svg class="size-4 transition-transform duration-150 group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
            {{ __('portal.show_data') }}
        </summary>
        <table class="mt-2 w-full text-left">
            <tbody class="divide-y divide-navy-900/10">
                @foreach ($rows as $label => $value)
                    <tr><th scope="row" class="py-2 pr-3 font-normal text-slate-700">{{ $label }}</th><td class="py-2 text-right font-semibold text-navy-900 tabular-nums">{{ \App\Services\Anonymizer::display($value, $suffix ?? '') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
