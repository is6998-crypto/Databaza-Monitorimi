{{-- The path of a criminal case as a rail of steps (App\Support\StageRail), at the top of the case overview. --}}
@php
    $steps = \App\Support\StageRail::for($getRecord());
@endphp
<div class="cw-rail-wrap">
    <ol class="cw-rail" style="--cw-rail-steps: {{ count($steps) }}">
        @foreach ($steps as $step)
            <li @class(['cw-rail-step', 'is-'.$step['state'], 'is-overdue' => $step['overdue'] !== null]) @if ($step['state'] === 'current') aria-current="step" @endif>
                <span class="cw-rail-node" aria-hidden="true">
                    @if ($step['state'] === 'done')
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 8.4 2.6 2.6L12 5.6"/></svg>
                    @endif
                </span>
                <span class="cw-rail-text">
                    <span class="cw-rail-label">{{ $step['label'] }}</span>
                    <span class="cw-rail-date">{{ $step['date'] ?? '—' }}</span>
                    @if ($step['overdue'] !== null)
                        <span class="cw-rail-alert">{{ __('deadlines.status.overdue') }} {{ $step['overdue'] }}</span>
                    @elseif ($step['state'] === 'current' && $step['detail'])
                        <span class="cw-rail-detail">{{ $step['detail'] }}</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ol>
</div>
