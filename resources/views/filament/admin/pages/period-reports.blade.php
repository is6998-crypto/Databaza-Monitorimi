{{-- Period reports (App\Filament\Admin\Pages\PeriodReports): choose a period, see what it covers, download. --}}
<x-filament-panels::page>
    <div class="cw-period">
        <form wire:submit="download" class="cw-panel cw-period-form" data-period-form>
            {{ $this->form }}

            <div class="cw-period-actions">
                <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray" wire:target="download">
                    {{ __('period_reports.form.download') }}
                </x-filament::button>
            </div>
        </form>

        <aside class="cw-panel cw-period-preview" data-period-preview>
            <p class="cw-period-eyebrow">{{ __('period_reports.form.preview') }}</p>
            <p class="cw-period-name">{{ $period }}</p>
            @if ($ready)
                <dl class="cw-period-figures">
                    <div>
                        <dt>{{ __('period_reports.document.cases_monitored') }}</dt>
                        <dd>{{ number_format($cases, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('dashboard.stats.hearings') }}</dt>
                        <dd>{{ number_format($hearings, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            @endif
            <p class="cw-period-note">{{ __('dashboard.stats.verified_only') }}</p>
        </aside>
    </div>
</x-filament-panels::page>
