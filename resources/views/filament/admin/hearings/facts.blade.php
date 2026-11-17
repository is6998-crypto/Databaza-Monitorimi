{{-- The four things one wants to know about a hearing at a glance, at the top of its page. --}}
@php
    /** @var \App\Models\Hearing $hearing */
    $hearing = $getRecord();

    $facts = [
        [
            'icon' => 'heroicon-o-calendar-days',
            'label' => __('hearings.facts.outcome'),
            'value' => $hearing->was_postponed ? __('hearings.facts.postponed') : __('hearings.facts.held'),
            'alert' => (bool) $hearing->was_postponed,
        ],
        [
            'icon' => 'heroicon-o-clock',
            'label' => __('hearings.facts.start'),
            'value' => $hearing->started_late
                ? __('hearings.facts.late').($hearing->delay_minutes ? ' · '.$hearing->delay_minutes.' min' : '')
                : __('hearings.facts.on_time'),
            'alert' => (bool) $hearing->started_late,
        ],
        [
            'icon' => 'heroicon-o-eye',
            'label' => __('hearings.facts.access'),
            'value' => match ($hearing->is_public) { true => __('hearings.answers.public'), false => __('hearings.answers.closed'), default => '—' },
            'alert' => $hearing->is_public === false,
        ],
        [
            'icon' => 'heroicon-o-microphone',
            'label' => __('hearings.facts.recording'),
            'value' => $hearing->recording?->getLabel() ?? '—',
            'alert' => false,
        ],
    ];
@endphp
<ul class="cw-facts">
    @foreach ($facts as $fact)
        <li @class(['cw-fact', 'is-alert' => $fact['alert']])>
            <span class="cw-fact-icon"><x-filament::icon :icon="$fact['icon']" class="size-5" /></span>
            <span class="cw-fact-text">
                <span class="cw-fact-label">{{ $fact['label'] }}</span>
                <span class="cw-fact-value">{{ $fact['value'] }}</span>
            </span>
        </li>
    @endforeach
</ul>
