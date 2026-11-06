@php($content = trans('portal.pages.'.$page))
@extends('portal.layout', ['title' => $content['title'], 'description' => $content['intro']])

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6" data-page="{{ $page }}">
        <h1 class="portal-heading">{{ $content['title'] }}</h1>
        <p class="mt-5 text-xl leading-relaxed text-slate-600">{{ $content['intro'] }}</p>
        <div class="portal-card mt-10 space-y-10 p-6 sm:p-10">
            @foreach ($content['sections'] as $section)
                <section>
                    <h2 class="flex items-center gap-3 text-xl font-bold text-navy-900 before:h-5 before:w-1 before:shrink-0 before:rounded-full before:bg-gold-400 before:content-['']">{{ $section['heading'] }}</h2>
                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="mt-3 leading-relaxed text-slate-700">{{ $paragraph }}</p>
                    @endforeach
                </section>
            @endforeach
        </div>
    </article>
@endsection
