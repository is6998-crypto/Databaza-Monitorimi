{{-- The dashboard's first band (App\Filament\Admin\Widgets\WelcomeWidget). The first action is the main one (yellow). --}}
<x-filament-widgets::widget class="cw-welcome">
    <div class="relative overflow-hidden rounded-2xl bg-navy-900 px-6 py-7 text-white sm:px-8 sm:py-8">
        @include('partials.logo-mark', ['class' => 'pointer-events-none absolute -right-10 -bottom-16 size-64 text-white/[.04]', 'color' => 'currentColor'])
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="cw-welcome-text">
                <p class="text-[13px] font-semibold text-gold-400">{{ $today }}</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-[1.75rem]">{{ __('dashboard.welcome.greeting', ['name' => $firstName]) }}</h2>
                @if ($role)
                    <p class="mt-1 text-[15px] text-white/70">{{ $role }}</p>
                @endif
            </div>
            @if ($actions !== [])
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($actions as $action)
                        <a href="{{ $action['url'] }}" @class([
                            'inline-flex min-h-10 items-center gap-2 rounded-xl px-4 text-sm font-semibold transition duration-150 hover:-translate-y-px active:translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400',
                            'bg-gold-400 text-navy-900 hover:bg-gold-300' => $loop->first,
                            'border border-white/20 text-white hover:border-white/40 hover:bg-white/10' => ! $loop->first,
                        ])>
                            <x-filament::icon :icon="$action['icon']" class="size-[18px]" />
                            {{ $action['label'] }}
                            @if (filled($action['count']))
                                <span @class(['rounded-full px-2 py-0.5 text-[12px] font-bold tabular-nums', 'bg-navy-900 text-white' => $loop->first, 'bg-gold-400 text-navy-900' => ! $loop->first])>{{ $action['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
