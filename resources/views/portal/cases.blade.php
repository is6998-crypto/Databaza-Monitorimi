@extends('portal.layout', ['title' => __('portal.cases.title'), 'description' => __('portal.cases.intro')])

@section('content')
    @include('portal.partials.page-header', ['title' => __('portal.cases.title'), 'intro' => __('portal.cases.intro')])
    <div class="portal-wrap">
        <livewire:portal.case-search />
    </div>
@endsection
