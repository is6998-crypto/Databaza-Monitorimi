{{--
    CourtWatch logo: the yellow frame mark and the name, with an optional subtitle.
    The name takes the surrounding text colour (white on navy, navy on light backgrounds).
--}}
<span class="cw-brand">
    @include('partials.logo-mark', ['class' => 'cw-brand-mark'])
    <span class="cw-brand-text">
        <span class="cw-brand-name">CourtWatch</span>
        @if (filled($subtitle ?? null))
            <span class="cw-brand-subtitle">{{ $subtitle }}</span>
        @endif
    </span>
</span>
