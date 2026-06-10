<?php

/**
 * @file
 */

/**
 * The houses we support
 */
class HOUSE {

    public const REPRESENTATIVES = 1;
    public const SENATE = 2;

    public const PRETTY = [
        self::REPRESENTATIVES => 'Representatives',
        self::SENATE => 'Senators',
    ];

}
