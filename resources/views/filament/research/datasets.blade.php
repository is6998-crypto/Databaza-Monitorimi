<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('research.datasets.intro') }}</p>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach (\App\Filament\Research\Pages\Datasets::datasetKeys() as $dataset)
            <x-filament::section :heading="__('research.datasets.'.$dataset.'.title')">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('research.datasets.'.$dataset.'.description') }}</p>
                <div class="mt-4">{{ ($this->downloadAction)(['dataset' => $dataset]) }}</div>
            </x-filament::section>
        @endforeach
    </div>
    <p class="text-xs text-gray-500">{{ __('research.datasets.logged') }}</p>
</x-filament-panels::page>
