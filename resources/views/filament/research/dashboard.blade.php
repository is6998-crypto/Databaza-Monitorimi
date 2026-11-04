<x-filament-panels::page>
    <div class="space-y-6" data-research-status="{{ $profile?->status->value ?? 'none' }}">
        @if (! $profile)
            <x-filament::section>{{ __('research.dashboard.no_profile') }}</x-filament::section>
        @elseif ($profile->status->value === 'pending')
            <x-filament::section icon="heroicon-o-clock" icon-color="warning" :heading="__('research.dashboard.pending_title')">
                <p>{{ __('research.dashboard.pending_body') }}</p>
            </x-filament::section>
        @elseif (in_array($profile->status->value, ['rejected', 'suspended'], true))
            <x-filament::section icon="heroicon-o-no-symbol" icon-color="danger" :heading="__('research.dashboard.rejected_title')">
                <p>{{ __('research.dashboard.rejected_body') }}</p>
                @if ($profile->review_note)<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $profile->review_note }}</p>@endif
            </x-filament::section>
        @elseif ($needsAgreement)
            <x-filament::section icon="heroicon-o-document-check" icon-color="info" :heading="__('research.dashboard.agreement_title')">
                <p>{{ __('research.dashboard.agreement_body') }}</p>
                <x-filament::button class="mt-4" tag="a" :href="\App\Filament\Research\Pages\DataUseAgreement::getUrl()">{{ __('research.agreement.title') }}</x-filament::button>
            </x-filament::section>
        @else
            <x-filament::section icon="heroicon-o-check-badge" icon-color="success" :heading="__('research.dashboard.approved_title')">
                <p>{{ __('research.dashboard.approved_body') }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-filament::button tag="a" :href="\App\Filament\Research\Pages\Datasets::getUrl()" icon="heroicon-o-circle-stack">{{ __('research.datasets.title') }}</x-filament::button>
                    <x-filament::button tag="a" color="gray" :href="\App\Filament\Research\Pages\ApiTokens::getUrl()" icon="heroicon-o-key">{{ __('research.tokens.title') }}</x-filament::button>
                    <x-filament::button tag="a" color="gray" :href="url('/docs/api')" icon="heroicon-o-book-open" target="_blank">{{ __('research.dashboard.api_docs') }}</x-filament::button>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section :heading="__('research.dashboard.rules_title')">
            <ul class="list-disc space-y-1 pl-5 text-sm">
                @foreach (__('research.dashboard.rules') as $rule)<li>{{ $rule }}</li>@endforeach
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
