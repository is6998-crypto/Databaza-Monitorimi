<article class="portal-card portal-card-link group flex flex-col overflow-hidden">
    <div class="relative flex h-32 items-end overflow-hidden bg-navy-900 p-5" aria-hidden="true">
        @include('portal.partials.frame-mark', ['class' => 'absolute -top-10 -right-8 size-44 text-white/[.07] transition-transform duration-500 ease-out group-hover:scale-[1.03]'])
        <span class="absolute inset-x-0 bottom-0 h-1 bg-gold-400"></span>
        <span class="relative text-[15px] font-bold text-gold-400">{{ $publication->type->getLabel() }}</span>
    </div>
    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <p class="portal-meta"><span class="sr-only">{{ $publication->type->getLabel() }} · </span>{{ $publication->published_on?->format('d.m.Y') }}</p>
        <h3 class="mt-2 text-lg leading-snug font-bold text-navy-900">
            <a href="{{ route('portal.publications.show', ['slug' => $publication->slug]) }}" class="portal-stretched decoration-gold-400 decoration-2 underline-offset-4 group-hover:underline">{{ $publication->title() }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 flex-1 text-[15px] leading-relaxed text-slate-700">{{ $publication->summary() }}</p>
    </div>
</article>
