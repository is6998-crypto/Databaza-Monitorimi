{{-- Coverage page (App\Filament\Admin\Pages\Coverage): one tile per check, then each check as a list. --}}
<x-filament-panels::page>
    <div class="cw-coverage" data-coverage>
        <nav class="cw-coverage-tiles" aria-label="{{ __('coverage.title') }}">
            @foreach ($sections as $section)
                <a href="#{{ $section['key'] }}" @class(['cw-coverage-tile', 'is-clear' => $section['total'] === 0]) data-coverage-tile="{{ $section['key'] }}">
                    <span class="cw-coverage-tile-value">{{ $section['total'] }}</span>
                    <span class="cw-coverage-tile-label">{{ $section['title'] }}</span>
                </a>
            @endforeach
        </nav>

        @foreach ($sections as $section)
            <section id="{{ $section['key'] }}" class="cw-panel" data-coverage-section="{{ $section['key'] }}">
                <header class="cw-panel-head">
                    <div>
                        <h2 class="cw-panel-title">
                            {{ $section['title'] }}
                            <span @class(['cw-count', 'is-zero' => $section['total'] === 0])>{{ $section['total'] }}</span>
                        </h2>
                        <p class="cw-panel-sub">{{ $section['description'] }}</p>
                    </div>
                    @if ($section['link'] && $section['total'] > 0)
                        <a href="{{ $section['link'] }}" class="cw-link-arrow">
                            {{ __('coverage.open_list') }}
                            <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                        </a>
                    @endif
                </header>

                @if ($section['rows'] === [])
                    <p class="cw-coverage-clear">
                        <x-filament::icon icon="heroicon-m-check-circle" class="size-5" />
                        {{ __('coverage.clear') }}
                    </p>
                @else
                    <ul class="cw-coverage-list">
                        @foreach ($section['rows'] as $row)
                            <li>
                                <a href="{{ $row['url'] }}" class="cw-coverage-row">
                                    <span class="cw-coverage-what">
                                        <span class="cw-coverage-case">
                                            {{ $row['title'] }}
                                            @if ($row['flag'])<x-filament::icon icon="heroicon-m-flag" class="cw-cal-flag" />@endif
                                        </span>
                                        <span class="cw-coverage-court">{{ $row['sub'] }}</span>
                                    </span>
                                    <span class="cw-coverage-detail">{{ $row['detail'] }}</span>
                                    <x-filament::icon icon="heroicon-m-chevron-right" class="cw-overdue-chevron" />
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($section['more'] > 0 || $section['expanded'])
                        <button type="button" class="cw-panel-more" wire:click="toggle('{{ $section['key'] }}')">
                            {{ $section['expanded'] ? __('coverage.show_less') : trans_choice('coverage.show_all', $section['total'], ['count' => $section['total']]) }}
                        </button>
                    @endif
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
