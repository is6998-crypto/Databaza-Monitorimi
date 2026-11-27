@php
    use App\Enums\ScheduledHearingStatus;
@endphp

<x-filament-panels::page>
    <div class="cw-cal" data-hearing-calendar>
        {{-- Month, navigation and filters --}}
        <div class="cw-cal-bar">
            <div class="cw-cal-nav">
                <button type="button" class="cw-cal-step" wire:click="previousMonth" aria-label="{{ __('calendar.previous') }}">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                </button>
                <h2 class="cw-cal-month" data-calendar-month>{{ $monthLabel }}</h2>
                <button type="button" class="cw-cal-step" wire:click="nextMonth" aria-label="{{ __('calendar.next') }}">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                </button>
                <button type="button" class="cw-cal-today" wire:click="thisMonth">{{ __('calendar.today') }}</button>
            </div>

            <div class="cw-cal-filters">
                <span class="cw-cal-summary">
                    {{ trans_choice('calendar.summary', $total, ['count' => $total]) }}
                    @if ($unstaffed > 0)
                        · <strong class="cw-cal-summary-alert">{{ trans_choice('calendar.summary_unstaffed', $unstaffed, ['count' => $unstaffed]) }}</strong>
                    @endif
                </span>
                <select wire:model.live="court" class="cw-cal-select" aria-label="{{ __('cases.fields.court') }}">
                    <option value="">{{ __('calendar.all_courts') }}</option>
                    @foreach ($courts as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if ($canFilterMine)
                    <label class="cw-cal-check">
                        <input type="checkbox" wire:model.live="mine">
                        <span>{{ __('calendar.filters.mine') }}</span>
                    </label>
                @endif
            </div>
        </div>

        {{-- Month grid (tablet and up) --}}
        <div class="cw-cal-grid" role="grid">
            @foreach ($weekdays as $weekday)
                <div class="cw-cal-head" role="columnheader">{{ $weekday }}</div>
            @endforeach

            @foreach ($weeks as $week)
                @foreach ($week as $cell)
                    <div @class(['cw-cal-day', 'is-out' => ! $cell['in_month'], 'is-today' => $cell['today'], 'is-past' => $cell['past']]) role="gridcell" data-day="{{ $cell['date'] }}">
                        <div class="cw-cal-day-top">
                            <span class="cw-cal-date">{{ $cell['day'] }}</span>
                            @if ($cell['create_url'] && $cell['in_month'])
                                <a href="{{ $cell['create_url'] }}" class="cw-cal-add" title="{{ __('calendar.add_on_day') }}" aria-label="{{ __('calendar.add_on_day') }}">+</a>
                            @endif
                        </div>
                        <ul class="cw-cal-items">
                            @foreach ($cell['items'] as $item)
                                <li>
                                    @include('filament.admin.calendar.item', ['item' => $item])
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- The same month as a list of days (phone) --}}
        <div class="cw-cal-agenda">
            @forelse ($agenda as $cell)
                <section @class(['cw-cal-agenda-day', 'is-today' => $cell['today']])>
                    <h3 class="cw-cal-agenda-date">{{ $cell['label'] }}</h3>
                    <ul class="cw-cal-items">
                        @foreach ($cell['items'] as $item)
                            <li>@include('filament.admin.calendar.item', ['item' => $item])</li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <p class="cw-cal-empty">{{ __('calendar.empty_month') }}</p>
            @endforelse
        </div>

        <p class="cw-cal-legend">
            <span class="cw-cal-key is-high"></span> {{ __('calendar.legend.unstaffed_high') }}
            <span class="cw-cal-key is-missed"></span> {{ ScheduledHearingStatus::Missed->getLabel() }}
            <span class="cw-cal-key is-attended"></span> {{ ScheduledHearingStatus::Attended->getLabel() }}
        </p>
    </div>
</x-filament-panels::page>
