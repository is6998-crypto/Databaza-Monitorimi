{{--
    Researcher home: a navy status band (where the account stands and what to do next, as the staff dashboard's
    welcome band) and the rules for using the data as a numbered list. The states and texts are the same as before.
--}}
@php
    $status = $profile?->status->value;
    $state = match (true) {
        ! $profile => ['title' => null, 'body' => __('research.dashboard.no_profile'), 'icon' => 'heroicon-o-user'],
        $status === 'pending' => ['title' => __('research.dashboard.pending_title'), 'body' => __('research.dashboard.pending_body'), 'icon' => 'heroicon-o-clock'],
        in_array($status, ['rejected', 'suspended'], true) => ['title' => __('research.dashboard.rejected_title'), 'body' => __('research.dashboard.rejected_body'), 'icon' => 'heroicon-o-no-symbol'],
        $needsAgreement => ['title' => __('research.dashboard.agreement_title'), 'body' => __('research.dashboard.agreement_body'), 'icon' => 'heroicon-o-document-check'],
        default => ['title' => __('research.dashboard.approved_title'), 'body' => __('research.dashboard.approved_body'), 'icon' => 'heroicon-o-check-badge'],
    };
    $actions = match (true) {
        ! $profile, $status === 'pending', in_array($status, ['rejected', 'suspended'], true) => [],
        $needsAgreement => [[__('research.agreement.title'), \App\Filament\Research\Pages\DataUseAgreement::getUrl(), 'heroicon-o-document-check', false]],
        default => [
            [__('research.datasets.title'), \App\Filament\Research\Pages\Datasets::getUrl(), 'heroicon-o-circle-stack', false],
            [__('research.tokens.title'), \App\Filament\Research\Pages\ApiTokens::getUrl(), 'heroicon-o-key', false],
            [__('research.dashboard.api_docs'), url('/docs/api'), 'heroicon-o-book-open', true],
        ],
    };
@endphp
<x-filament-panels::page>
    <div class="space-y-6" data-research-status="{{ $status ?? 'none' }}">
        <section class="cw-welcome relative overflow-hidden rounded-2xl bg-navy-900 px-6 py-8 text-white sm:px-8 sm:py-10">
            @include('partials.logo-mark', ['class' => 'pointer-events-none absolute -right-12 -bottom-20 size-72 text-white/[.04]', 'color' => 'currentColor'])
            <div class="relative max-w-3xl">
                <span class="flex size-11 items-center justify-center rounded-xl bg-white/10 text-gold-400">
                    <x-filament::icon :icon="$state['icon']" class="size-6" />
                </span>
                @if ($state['title'])
                    <h2 class="mt-5 text-2xl font-bold tracking-tight sm:text-[1.75rem]">{{ $state['title'] }}</h2>
                @endif
                <p class="mt-2 text-[16px] leading-relaxed text-white/80">{{ $state['body'] }}</p>
                @if (in_array($status, ['rejected', 'suspended'], true) && $profile->review_note)
                    <p class="mt-3 rounded-xl bg-white/10 px-4 py-3 text-[15px] text-white/85">{{ $profile->review_note }}</p>
                @endif
                @if ($actions !== [])
                    <div class="mt-7 flex flex-wrap gap-2.5">
                        @foreach ($actions as [$label, $url, $icon, $external])
                            <a href="{{ $url }}" @if ($external) target="_blank" rel="noopener" @endif @class([
                                'inline-flex min-h-10 items-center gap-2 rounded-xl px-4 text-sm font-semibold transition duration-150 hover:-translate-y-px active:translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400',
                                'bg-gold-400 text-navy-900 hover:bg-gold-300' => $loop->first,
                                'border border-white/20 text-white hover:border-white/40 hover:bg-white/10' => ! $loop->first,
                            ])>
                                <x-filament::icon :icon="$icon" class="size-[18px]" />
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <x-filament::section :heading="__('research.dashboard.rules_title')">
            <ol class="grid gap-3 md:grid-cols-2">
                @foreach (__('research.dashboard.rules') as $rule)
                    <li class="flex gap-3 rounded-xl bg-gray-50 p-4 text-[15px] leading-relaxed text-gray-700 dark:bg-white/5 dark:text-gray-300">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-navy-900 text-[13px] font-bold text-white dark:bg-white dark:text-navy-900">{{ $loop->iteration }}</span>
                        <span>{{ $rule }}</span>
                    </li>
                @endforeach
            </ol>
        </x-filament::section>
    </div>
</x-filament-panels::page>
