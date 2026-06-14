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

    /**
     * Return the pretty display name for a house.
     *
     * @param int $house
     *   House identifier.
     * @param int|null $default_house
     *   Optional fallback house identifier.
     * @return string
     *   Pretty house name, or an empty string when unavailable.
     */
    public static function pretty_name($house, $default_house = null) {
        // This is used both for the name of the house: "House of Representatives" and for the plural name of the member e.g. "some Representatives".
        if (isset(self::PRETTY[$house])) {
            return self::PRETTY[$house];
        }
        if ($default_house !== null && isset(self::PRETTY[$default_house])) {
            return self::PRETTY[$default_house];
        }
        return '';
    }

}
