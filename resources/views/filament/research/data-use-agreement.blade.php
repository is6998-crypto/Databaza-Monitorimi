<x-filament-panels::page>
    <x-filament::section :heading="__('research.agreement.version', ['version' => config('courtwatch.data_use_agreement_version')])">
        <ol class="list-decimal space-y-2 pl-5 text-sm" data-agreement>
            @foreach (__('research.agreement.clauses') as $clause)<li>{{ $clause }}</li>@endforeach
        </ol>
        @php($profile = auth()->user()?->researcherProfile)
        @if ($profile?->hasAcceptedCurrentAgreement())
            <p class="mt-4 text-sm font-medium text-success-600">{{ __('research.agreement.accepted_on', ['date' => $profile->agreement_accepted_at?->setTimezone(config('courtwatch.display_timezone'))->format('d.m.Y H:i')]) }}</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
