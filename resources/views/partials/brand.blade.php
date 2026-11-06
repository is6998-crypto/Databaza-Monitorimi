{{--
    CourtWatch logo: the yellow frame mark and the name, with an optional subtitle.
    The name takes the surrounding text colour (white on navy, navy on light backgrounds).
--}}
<span class="cw-brand">
    <svg class="cw-brand-mark" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><path d="M7.5 7.5h19v6h6v19h-19v-6h-6z" fill="none" stroke="#f8c33a" stroke-width="5"/></svg>
    <span class="cw-brand-text">
        <span class="cw-brand-name">CourtWatch</span>
        @if (filled($subtitle ?? null))
            <span class="cw-brand-subtitle">{{ $subtitle }}</span>
        @endif
    </span>
</span>
