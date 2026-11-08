@php($content = trans('portal.pages.'.$page))
@extends('portal.layout', ['title' => $content['title'], 'description' => $content['intro']])

@section('content')
    @include('portal.partials.page-header', ['title' => $content['title'], 'intro' => $content['intro'], 'width' => 'max-w-3xl'])
    <article class="wrap portal-wrap mt-10" data-page="{{ $page }}">
        <div class="card portal-card max-w-3xl divide-y divide-navy-900/10">
            @foreach ($content['sections'] as $section)
                <section class="p-6 sm:p-10">
                    <h2 class="flex items-center gap-3 text-xl font-bold text-navy-900 before:h-5 before:w-1 before:shrink-0 before:rounded-full before:bg-gold-400 before:content-['']">{{ $section['heading'] }}</h2>
                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="mt-3 max-w-[68ch] text-base leading-relaxed text-slate-700">{{ $paragraph }}</p>
                    @endforeach
                </section>
            @endforeach
        </div>
    </article>
@endsection
