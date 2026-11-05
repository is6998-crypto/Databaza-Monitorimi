<?php

namespace App\Support;

/**
 * CourtWatch visual identity: navy and yellow on white and light grey, nothing else (no red,
 * orange or green). Used by both Filament panels, the charts and the inline-styled pages
 * (error pages, idle warning). The same values are defined for Tailwind in
 * resources/css/brand.css.
 *
 * Filament's semantic colours are mapped onto the two brand colours: warnings use yellow,
 * success, information and errors use navy. Badges stay distinguishable by style (solid,
 * tinted or outlined, see the panel theme) and by their label and icon.
 */
final class Brand
{
    public const NAVY = '#0c2a42';

    /** A lighter navy for a second data series. */
    public const NAVY_SOFT = '#52769d';

    public const YELLOW = '#f8c33a';

    public const CANVAS = '#f5f6f7';

    public const FONT = 'Plus Jakarta Sans Variable';

    /**
     * Categorical chart colours, in order of use: navy and yellow shades, then greys.
     *
     * @var list<string>
     */
    public const CHART_PALETTE = ['#0c2a42', '#f8c33a', '#52769d', '#fde38f', '#94a0ae', '#183852', '#fbd35c', '#b3c6da', '#364254', '#e2e5ea'];

    /**
     * Light tints with navy initials, for user avatars.
     *
     * @var list<string>
     */
    public const AVATAR_TINTS = ['#fef0c3', '#d9e3ee', '#e2e5ea', '#fde38f', '#b3c6da', '#eceef1'];

    /**
     * Yellow for actions, highlights and warnings. Shades 700-950 are navy on purpose:
     * Filament picks the colour of text on a yellow button or badge, and of text in the
     * yellow role on white, from this palette by contrast; the design puts navy text on
     * yellow and never uses dark yellow (it reads as brown or orange).
     *
     * @var array<int, string>
     */
    private const YELLOW_PALETTE = [
        50 => '#fffaeb',
        100 => '#fef0c3',
        200 => '#fde38f',
        300 => '#fbd35c',
        400 => '#f8c33a',
        500 => '#f1b92c',
        600 => '#e8ae1f',
        700 => '#0c2a42',
        800 => '#0a2236',
        900 => '#081c2d',
        950 => '#06192a',
    ];

    /**
     * Navy for success, information and errors. Shade 600 is the brand navy, so solid
     * buttons are exactly #0c2a42 with white text.
     *
     * @var array<int, string>
     */
    private const NAVY_PALETTE = [
        50 => '#eef3f8',
        100 => '#d9e3ee',
        200 => '#b3c6da',
        300 => '#839fbe',
        400 => '#52769d',
        500 => '#234667',
        600 => '#0c2a42',
        700 => '#0a2337',
        800 => '#081c2d',
        900 => '#061523',
        950 => '#040f19',
    ];

    /**
     * Neutral greys with a hint of navy (the darkest shades form the dark mode).
     *
     * @var array<int, string>
     */
    private const GRAY = [
        50 => '#f5f6f7',
        100 => '#eceef1',
        200 => '#e2e5ea',
        300 => '#cbd1d9',
        400 => '#94a0ae',
        500 => '#677386',
        600 => '#4b5768',
        700 => '#364254',
        800 => '#1f2b3b',
        900 => '#121f30',
        950 => '#091522',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function filamentColors(): array
    {
        return [
            'primary' => self::YELLOW_PALETTE,
            'warning' => self::YELLOW_PALETTE,
            'success' => self::NAVY_PALETTE,
            'info' => self::NAVY_PALETTE,
            'danger' => self::NAVY_PALETTE,
            'gray' => self::GRAY,
        ];
    }
}
