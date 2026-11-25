<?php

namespace OpenAustralia\TWFY\Enums;

/**
 * The two houses of the Australian Parliament, as member.house numbers them.
 */
enum House: int {

    case Representatives = 1;
    case Senate = 2;

    /**
     * The personinfo key counting the debates a member spoke in over the last year.
     */
    public function debatesKey(): string {
        return match ($this) {
            self::Representatives => 'debate_sectionsspoken_inlastyear',
            self::Senate => 'Ldebate_sectionsspoken_inlastyear',
        };
    }

    /**
     * The first part of the address of a member's profile.
     */
    public function profilePath(): string {
        return match ($this) {
            self::Representatives => 'mp/',
            self::Senate => 'senator/',
        };
    }

}
