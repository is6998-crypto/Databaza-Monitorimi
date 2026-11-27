{{-- Dashboard list of the hearings of the next seven days (App\Filament\Admin\Widgets\UpcomingHearingsWidget). --}}
<x-filament-widgets::widget class="cw-upcoming">
    <section class="cw-panel">
        <header class="cw-panel-head">
            <div>
                <h2 class="cw-panel-title">
                    {{ __('calendar.widget.title') }}
                    @if ($total > 0)<span class="cw-count">{{ $total }}</span>@endif
                </h2>
                <p class="cw-panel-sub">{{ __('calendar.widget.sub') }}</p>
            </div>
            <a href="{{ $allUrl }}" class="cw-link-arrow">
                {{ __('calendar.widget.all') }}
                <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
            </a>
        </header>

        @if ($rows === [])
            <p class="cw-upcoming-empty">{{ __('calendar.widget.empty') }}</p>
        @else
            <ul class="cw-upcoming-list">
                @foreach ($rows as $row)
                    <li>
                        <a href="{{ $row['url'] }}" @class(['cw-upcoming-row', 'is-unstaffed' => $row['monitor'] === null, 'is-high' => $row['high']])>
                            <span class="cw-upcoming-date" aria-hidden="true">
                                <span class="cw-upcoming-day">{{ $row['day'] }}</span>
                                <span class="cw-upcoming-month">{{ $row['month'] }}</span>
                            </span>
                            <span class="cw-upcoming-what">
                                <span class="cw-upcoming-case">
                                    {{ $row['case'] }}
                                    @if ($row['high'])<x-filament::icon icon="heroicon-m-flag" class="cw-cal-flag" />@endif
                                </span>
                                <span class="cw-upcoming-court">{{ collect([$row['time'], $row['court']])->filter()->implode(' · ') }}</span>
                            </span>
                            <span class="cw-upcoming-who">
                                @if ($row['monitor'] === null)
                                    <span class="cw-upcoming-nobody">{{ __('calendar.nobody') }}</span>
                                @else
                                    {{ $row['mine'] ? __('calendar.filters.mine_short') : $row['monitor'] }}
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($more > 0)
            <a href="{{ $allUrl }}" class="cw-panel-more">{{ trans_choice('deadlines.widget.more', $more, ['count' => $more]) }}</a>
        @endif
    </section>
</x-filament-widgets::widget>
