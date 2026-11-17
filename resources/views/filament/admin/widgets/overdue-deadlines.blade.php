{{-- Dashboard list of the legal time limits exceeded right now (App\Filament\Admin\Widgets\OverdueDeadlinesWidget). --}}
<x-filament-widgets::widget class="cw-overdue">
    <section class="cw-panel">
        <header class="cw-panel-head">
            <div>
                <h2 class="cw-panel-title">
                    {{ __('deadlines.widget.title') }}
                    <span class="cw-count">{{ $total }}</span>
                </h2>
                <p class="cw-panel-sub">{{ __('deadlines.widget.description') }}</p>
            </div>
            <a href="{{ $allUrl }}" class="cw-link-arrow">
                {{ __('deadlines.widget.all') }}
                <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
            </a>
        </header>

        <ul class="cw-overdue-list">
            @foreach ($rows as $row)
                <li>
                    <a href="{{ $row['url'] }}" class="cw-overdue-row">
                        <span class="cw-overdue-what">
                            <span class="cw-overdue-phase">{{ $row['phase'] }}</span>
                            <span class="cw-overdue-case">{{ $row['case'] }}@if ($row['court']) <span aria-hidden="true">·</span> {{ $row['court'] }}@endif</span>
                        </span>

                        <span class="cw-meter" role="img" aria-label="{{ __('deadlines.meter.over', ['over' => $row['over'], 'limit' => $row['limit']]) }}">
                            <span class="cw-meter-track">
                                <span class="cw-meter-limit" style="width: {{ $row['limitShare'] }}%"></span>
                                <span class="cw-meter-over"></span>
                            </span>
                            <span class="cw-meter-legend" aria-hidden="true">
                                <span>{{ __('deadlines.meter.limit', ['limit' => $row['limit']]) }} · {{ $row['article'] }}</span>
                            </span>
                        </span>

                        <span class="cw-overdue-days">
                            <span class="cw-overdue-plus">+{{ $row['over'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="cw-overdue-chevron" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($more > 0)
            <a href="{{ $allUrl }}" class="cw-panel-more">{{ trans_choice('deadlines.widget.more', $more, ['count' => $more]) }}</a>
        @endif
    </section>
</x-filament-widgets::widget>
