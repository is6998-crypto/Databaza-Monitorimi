{{--
    Ranked horizontal bars as plain HTML (the comparisons on the home page): one hue (navy) on a lighter track of
    the same ramp, 10px thick with a rounded data end and a square baseline, the label above each bar and the value
    at its right, nothing clipped at any width. Suppressed values (null) print "<5" and draw no bar.
    Pass every parameter: @include hands the parent view's variables down, so a missing one can leak in.

    $title   the card heading
    $rows    [label => int|float|null]
    $suffix  printed after each value (' %')
    $scale   'max' (default: the largest value is the full track) or 'percent' (0-100, for shares)
    $ranked  sort largest first (default true; false keeps an order that means something, such as stages)
    $split   lay a long list out in two columns from the lg breakpoint (default false; for a card that spans the grid)
--}}
@php
    use App\Services\Anonymizer;
    $rows = collect($rows);
    if ($ranked ?? true) {
        $rows = $rows->sortByDesc(fn ($value) => $value ?? -1);
    }
    $max = ($scale ?? 'max') === 'percent' ? 100 : max(1, (float) $rows->filter()->max());
@endphp
<div class="card portal-card flex flex-col p-5 sm:p-6">
    <h3 class="text-lg leading-snug font-bold text-navy-900">{{ $title }}</h3>
    <ol @class(['mt-4', 'space-y-2.5' => ! ($split ?? false), 'grid gap-x-10 gap-y-2.5 lg:grid-cols-2' => $split ?? false]) role="list">
        @foreach ($rows as $label => $value)
            <li class="group">
                <div class="flex items-baseline justify-between gap-4 text-[15px] leading-snug">
                    <span class="min-w-0 text-slate-700">{{ $label }}</span>
                    <span class="shrink-0 font-semibold text-navy-900 tabular-nums">{{ Anonymizer::display($value, $suffix ?? '') }}</span>
                </div>
                <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-navy-50" aria-hidden="true">
                    @if ($value !== null && $value > 0)
                        <div class="h-full rounded-r-[4px] bg-navy-900 transition-colors duration-150 group-hover:bg-navy-600" style="width: {{ round(min(100, $value / $max * 100), 2) }}%"></div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
