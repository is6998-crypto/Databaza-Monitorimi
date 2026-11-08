{{--
    The brand panel beside the sign-in, two-factor and password pages of both panels (render hook
    SIMPLE_LAYOUT_START, guests only): the logo, the tagline set large and the promise that matters to
    the people signing in. Hidden under 1024px, where the form stands alone. No Filament view is overridden.
--}}
@guest
    <aside class="cw-auth-aside">
        @include('portal.partials.frame-mark', ['class' => 'cw-auth-aside-mark'])
        <div class="cw-auth-aside-brand">@include('partials.brand', ['subtitle' => $subtitle])</div>
        <div class="cw-auth-aside-body">
            <p class="cw-auth-aside-title">{{ __('portal.tagline') }}</p>
            <p class="cw-auth-aside-note"><span class="cw-auth-aside-dot"></span>{{ $note }}</p>
        </div>
    </aside>
@endguest
