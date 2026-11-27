<?php

namespace OpenAustralia\TWFY;

/**
 * Line icons used on more than one page, as SVG path data for a 24 by 24 grid.
 *
 * Keeping them here means the same action looks the same on every page.
 */
final class Icons {

    /**
     * A bell, for starting email alerts.
     */
    public const BELL = 'M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0';

    /**
     * A bell with a line through it, for stopping email alerts.
     */
    public const BELL_OFF = 'M8.7 3A6 6 0 0 1 18 8a21.3 21.3 0 0 0 .6 5M17 17H3s3-2 3-9a4.67 4.67 0 0 1 .3-1.7M10.3 21a1.94 1.94 0 0 0 3.4 0M2 2l20 20';

}
