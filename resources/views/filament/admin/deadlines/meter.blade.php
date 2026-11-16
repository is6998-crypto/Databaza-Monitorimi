{{-- How much of a legal time limit is used: a bar from the day the phase started to the day the limit ends. --}}
@php
    /** @var \App\Models\CaseDeadline $deadline */
    $deadline = $getRecord();
    $status = $deadline->status();
    $format = (string) config('courtwatch.date_format');
    $limit = $deadline->limitLabel();

    $caption = match (true) {
        $status === 'overdue', $status === 'late' => __('deadlines.meter.over', ['over' => trans_choice('deadlines.units.days', $deadline->daysOver(), ['count' => $deadline->daysOver()]), 'limit' => $limit]),
        $status === 'met' => __('deadlines.meter.took', ['days' => trans_choice('deadlines.units.days', $deadline->elapsedDays(), ['count' => $deadline->elapsedDays()])]),
        $status === 'closed', $status === 'paused' => $deadline->state->getLabel(),
        default => __('deadlines.meter.day_of', ['day' => $deadline->elapsedDays(), 'limit' => $limit]),
    };
@endphp
<div class="cw-tl" data-status="{{ $status }}">
    <div class="cw-tl-dates">
        <span>{{ $deadline->started_on->format($format) }}</span>
        <span>{{ $deadline->due_on->format($format) }}</span>
    </div>
    @if ($deadline->daysOver() > 0)
        {{-- Past the limit: the limit itself in navy, the days over it in yellow. --}}
        <div class="cw-meter-track cw-tl-split" role="img" aria-label="{{ $caption }}">
            <span class="cw-meter-limit" style="width: {{ $deadline->limitShare() }}%"></span>
            <span class="cw-meter-over"></span>
        </div>
    @else
        <div class="cw-tl-track" role="img" aria-label="{{ $caption }}">
            <span class="cw-tl-fill" style="width: {{ $deadline->usedPercent() }}%"></span>
        </div>
    @endif
    <p class="cw-tl-caption">{{ $caption }}</p>
</div>
