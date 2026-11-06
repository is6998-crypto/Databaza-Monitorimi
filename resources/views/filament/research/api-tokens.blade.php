<x-filament-panels::page>
    @if ($plainTextToken)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning" :heading="__('research.tokens.copy_now')">
            <code class="block break-all rounded bg-gray-100 p-3 text-sm dark:bg-white/5" data-plain-token>{{ $plainTextToken }}</code>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('research.tokens.usage', ['url' => url('/api/v1/cases')]) }}</p>
        </x-filament::section>
    @endif
    {{ $this->table }}
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('research.tokens.docs') }} <a href="{{ url('/docs/api') }}" class="font-medium text-primary-600 underline dark:text-primary-400" target="_blank" rel="noopener">{{ url('/docs/api') }}</a></p>
</x-filament-panels::page>
