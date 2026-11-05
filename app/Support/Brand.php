<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * CourtWatch visual identity: navy, yellow and green on a light grey canvas. Used by both
 * Filament panels, the charts and the inline-styled pages (error pages, idle warning).
 * The same values are defined for Tailwind in resources/css/brand.css.
 */
final class Brand
{
    public const NAVY = '#0c2a42';

    public const YELLOW = '#f8c33a';

    public const GREEN = '#10937b';

    public const CANVAS = '#f5f6f7';

    public const FONT = 'Plus Jakarta Sans Variable';

    /**
     * Categorical chart colours, in order of use.
     *
     * @var list<string>
     */
    public const CHART_PALETTE = ['#0c2a42', '#f8c33a', '#10937b', '#4878a9', '#e8772e', '#839fbe', '#c98d0f', '#74d2b4', '#b4413a', '#94a0ae'];

    /**
     * Light tints with navy initials, for user avatars.
     *
     * @var list<string>
     */
    public const AVATAR_TINTS = ['#fef0c3', '#e1ebf5', '#d3f3e7', '#e2e5ea', '#fde38f', '#c4d6ea'];

    /**
     * Yellow for actions and highlights. The darkest shades are navy on purpose: Filament
     * picks the text colour on a yellow button or badge from this palette, and the design
     * puts navy text on yellow.
     *
     * @var array<int, string>
     */
    private const PRIMARY = [
        50 => '#fffaeb',
        100 => '#fef0c3',
        200 => '#fde38f',
        300 => '#fbd35c',
        400 => '#f8c33a',
        500 => '#e9ad1d',
        600 => '#c98d0f',
        700 => '#9a6a0c',
        800 => '#0c2a42',
        900 => '#0a2236',
        950 => '#06192a',
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
     * @var array<int, string>
     */
    private const SUCCESS = [
        50 => '#ecfaf5',
        100 => '#d3f3e7',
        200 => '#a9e6d0',
        300 => '#74d2b4',
        400 => '#3db898',
        500 => '#17a283',
        600 => '#10937b',
        700 => '#0d7563',
        800 => '#0d5d50',
        900 => '#0b4c42',
        950 => '#052c27',
    ];

    /**
     * @var array<int, string>
     */
    private const INFO = [
        50 => '#f0f5fa',
        100 => '#e1ebf5',
        200 => '#c4d6ea',
        300 => '#9bbad9',
        400 => '#6b97c2',
        500 => '#4878a9',
        600 => '#3a6290',
        700 => '#2f5076',
        800 => '#284262',
        900 => '#233851',
        950 => '#172436',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function filamentColors(): array
    {
        return [
            'primary' => self::PRIMARY,
            'gray' => self::GRAY,
            'success' => self::SUCCESS,
            'info' => self::INFO,
            'warning' => Color::Orange,
            'danger' => Color::Red,
        ];
    }
}
