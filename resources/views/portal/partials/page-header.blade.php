{{--
    The navy band every inner page opens with, matching the home page's hero.
    $title, optional $intro, $kicker, $back = [url, label], $width (max-width class of the wrap's content).
--}}
<div class="relative overflow-hidden bg-navy-900 text-white">
    @include('portal.partials.frame-mark', ['class' => 'pointer-events-none absolute -right-16 -bottom-24 hidden size-80 text-white/[.03] lg:block'])
    <div class="portal-wrap relative pt-10 pb-12 sm:pt-12 sm:pb-16">
        <div @class(['space-y-4', $width ?? 'max-w-4xl']) data-enter>
            @isset($back)
                <a href="{{ $back[0] }}" class="group inline-flex min-h-10 items-center gap-2 text-[15px] font-semibold text-white/80 transition-colors hover:text-white">
                    <svg class="size-4 transition-transform duration-150 group-hover:-translate-x-[3px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6 6-6-6 6-6"/></svg>
                    {{ $back[1] }}
                </a>
            @endisset
            @if (filled($kicker ?? null))
                <p class="portal-kicker">{{ $kicker }}</p>
            @endif
            <h1 class="portal-heading">{{ $title }}</h1>
            @if (filled($intro ?? null))
                <p class="portal-lead text-white/80">{{ $intro }}</p>
            @endif
        </div>
    </div>
</div>
