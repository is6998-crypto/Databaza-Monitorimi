@php
    /** @var array<string, mixed> $item */
    $status = $item['status'];
    $tag = $item['url'] ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($item['url']) href="{{ $item['url'] }}" @endif
    @class([
        'cw-cal-item',
        'is-'.$status->value,
        'is-high' => $item['high'],
        'needs-monitor' => $item['needs_monitor'],
    ])
    data-calendar-item="{{ $item['id'] }}"
    title="{{ collect([$item['case'], $item['court'], $item['courtroom'], $item['monitor'] ?? __('calendar.nobody'), $status->getLabel()])->filter()->implode(' · ') }}"
>
    <span class="cw-cal-item-line">
        @if ($item['time'])<span class="cw-cal-time">{{ $item['time'] }}</span>@endif
        <span class="cw-cal-case">{{ $item['case'] }}</span>
        @if ($item['high'])<x-filament::icon icon="heroicon-m-flag" class="cw-cal-flag" />@endif
    </span>
    <span class="cw-cal-meta">
        @if ($item['needs_monitor'])
            {{ __('calendar.nobody') }}
        @elseif ($status === \App\Enums\ScheduledHearingStatus::Upcoming)
            {{ $item['monitor'] }}
        @else
            {{ $status->getLabel() }}
        @endif
    </span>
</{{ $tag }}>
