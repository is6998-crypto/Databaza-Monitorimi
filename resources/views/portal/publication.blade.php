@extends('portal.layout', ['title' => $publication->title(), 'description' => \Illuminate\Support\Str::limit((string) $publication->summary(), 155)])

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a href="{{ route('portal.publications') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← {{ __('portal.nav.publications') }}</a>
        <p class="mt-6 text-sm font-semibold uppercase tracking-wide text-indigo-700">{{ $publication->type->getLabel() }} · {{ $publication->published_on?->format('d.m.Y') }}</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $publication->title() }}</h1>
        <div class="prose mt-6 max-w-none text-slate-700">{!! nl2br(e((string) $publication->summary())) !!}</div>
        @if ($publication->file_path)
            <a href="{{ route('portal.publications.download', ['slug' => $publication->slug]) }}" class="mt-8 inline-flex rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-600">{{ __('portal.publications.download') }}</a>
        @endif
    </article>
@endsection
