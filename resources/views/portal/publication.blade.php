@extends('portal.layout', ['title' => $publication->title(), 'description' => \Illuminate\Support\Str::limit((string) $publication->summary(), 155)])

@section('content')
    @include('portal.partials.page-header', [
        'title' => $publication->title(),
        'kicker' => $publication->type->getLabel().' · '.$publication->published_on?->format('d.m.Y'),
        'back' => [route('portal.publications'), __('portal.nav.publications')],
        'width' => 'max-w-3xl',
    ])
    <article class="wrap portal-wrap mt-10">
        <div class="max-w-3xl">
            <div class="card portal-card p-6 text-base leading-relaxed text-slate-700 sm:p-10">{!! nl2br(e((string) $publication->summary())) !!}</div>
            @if ($publication->file_path)
                <a href="{{ route('portal.publications.download', ['slug' => $publication->slug]) }}" class="portal-btn-primary mt-8">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    {{ __('portal.publications.download') }}
                </a>
            @endif
        </div>
    </article>
@endsection
