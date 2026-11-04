<article class="flex flex-col rounded-2xl border border-slate-200 p-5">
    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $publication->type->getLabel() }} · {{ $publication->published_on?->format('d.m.Y') }}</p>
    <h3 class="mt-2 text-lg font-semibold text-slate-900">
        <a href="{{ route('portal.publications.show', ['slug' => $publication->slug]) }}" class="hover:underline">{{ $publication->title() }}</a>
    </h3>
    <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-600">{{ $publication->summary() }}</p>
</article>
