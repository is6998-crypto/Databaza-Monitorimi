@php($content = trans('portal.pages.'.$page))
@extends('portal.layout', ['title' => $content['title'], 'description' => $content['intro']])

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6" data-page="{{ $page }}">
        <h1 class="text-3xl font-bold text-slate-900">{{ $content['title'] }}</h1>
        <p class="mt-4 text-lg text-slate-600">{{ $content['intro'] }}</p>
        @foreach ($content['sections'] as $section)
            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">{{ $section['heading'] }}</h2>
                @foreach ($section['paragraphs'] as $paragraph)
                    <p class="mt-3 text-slate-700">{{ $paragraph }}</p>
                @endforeach
            </section>
        @endforeach
    </article>
@endsection
