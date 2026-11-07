{{--
    The CourtWatch mark: a thick yellow square frame with its top-left and bottom-right corners cut on the
    diagonal. The one copy of the drawing; $class sizes it, $color fills it (brand yellow by default; pass
    'currentColor' to take the text colour). public/icons/favicon.svg and the app icons draw the same path.
--}}
<svg class="{{ $class ?? '' }}" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><path fill="{{ $color ?? '#f8c33a' }}" fill-rule="evenodd" d="M10 4h26v26l-6 6H4V10zM9.5 9.5v21h21v-21z"/></svg>
