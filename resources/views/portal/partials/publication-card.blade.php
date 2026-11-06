<article class="portal-card group flex flex-col overflow-hidden transition hover:shadow-lg hover:shadow-navy-900/5">
    <div class="relative h-28 overflow-hidden bg-navy-900 text-sm text-white" aria-hidden="true">
        <div class="absolute inset-0 bg-white/5 [clip-path:polygon(0_100%,100%_10%,100%_45%,35%_100%)]"></div>
        <div class="absolute inset-0 bg-gold-400 [clip-path:polygon(60%_100%,100%_45%,100%_100%)]"></div>
        <span class="absolute top-4 left-5">@include('partials.brand')</span>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase">{{ $publication->type->getLabel() }} · {{ $publication->published_on?->format('d.m.Y') }}</p>
        <h3 class="mt-2 text-lg leading-snug font-bold text-navy-900">
            <a href="{{ route('portal.publications.show', ['slug' => $publication->slug]) }}" class="decoration-gold-400 decoration-2 underline-offset-4 hover:underline">{{ $publication->title() }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600">{{ $publication->summary() }}</p>
    </div>
</article>
