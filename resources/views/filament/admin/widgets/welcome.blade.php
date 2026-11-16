{{--
    The dashboard's first band (App\Filament\Admin\Widgets\WelcomeWidget): who is signed in, what they can start,
    and a row of live counts of what is waiting for them. The first action is the main one (yellow).
--}}
<x-filament-widgets::widget class="cw-welcome">
    <div class="cw-hero">
        @include('partials.logo-mark', ['class' => 'cw-hero-mark', 'color' => 'currentColor'])

        <div class="cw-hero-top">
            <div class="cw-welcome-text">
                <p class="cw-hero-date">{{ $today }}</p>
                <h2 class="cw-hero-title">{{ __('dashboard.welcome.greeting', ['name' => $firstName]) }}</h2>
                @if ($role)
                    <p class="cw-hero-role">{{ $role }}</p>
                @endif
            </div>

            @if ($actions !== [])
                <div class="cw-hero-actions">
                    @foreach ($actions as $action)
                        <a href="{{ $action['url'] }}" @class(['cw-hero-btn', 'cw-hero-btn-primary' => $loop->first])>
                            <x-filament::icon :icon="$action['icon']" class="size-[18px]" />
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($pulse !== [])
            <ul class="cw-pulse" style="--cw-pulse-count: {{ count($pulse) }}">
                @foreach ($pulse as $item)
                    <li>
                        <a href="{{ $item['url'] }}" @class(['cw-pulse-tile', 'cw-pulse-alert' => $item['alert']])>
                            <span class="cw-pulse-head">
                                <x-filament::icon :icon="$item['icon']" class="cw-pulse-icon" />
                                <span class="cw-pulse-label">{{ $item['label'] }}</span>
                            </span>
                            <span class="cw-pulse-foot">
                                <span class="cw-pulse-value">{{ number_format($item['value'], 0, ',', '.') }}</span>
                                <x-filament::icon icon="heroicon-m-arrow-up-right" class="cw-pulse-arrow" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-widgets::widget>
