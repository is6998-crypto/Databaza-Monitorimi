{{-- ECharts chart with an accessible data table. $option: ECharts option array. $rows: [label => value|null]. --}}
{{-- The title is the card's heading (an h3 under the section's h2), not a caption under the drawing. --}}
<div class="card portal-card flex flex-col p-5 sm:p-6">
    <h3 class="text-lg leading-snug font-bold text-navy-900">{{ $title }}</h3>
    <div class="mt-4 h-72 w-full" wire:key="chart-{{ md5(json_encode($option)) }}" x-data="echart(@js($option))" wire:ignore role="img" aria-label="{{ $title }}"></div>
    <details class="group mt-2 border-t border-navy-900/10 pt-1 text-[15px]">
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
</div>
