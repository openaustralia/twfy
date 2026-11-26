<?php

namespace OpenAustralia\TWFY\Parliament;

use OpenAustralia\TWFY\Enums\House;

/**
 * The addresses of the member pages, and of what they link to about a person. None are encoded for HTML.
 */
final class MemberUrls {

    /**
     * A profile, by name and, if given, electorate or state.
     */
    public static function profile(House $house, string $name, string $constituency = ''): string {
        return WEBPATH . $house->profilePath() . make_member_url($name, $constituency, $house->value);
    }

    /**
     * Part of an address as words. Underscores stand for spaces, as "+" does in old links.
     */
    public static function words(string $segment): string {
        return str_replace(['_', '+'], ' ', $segment);
    }

    /**
     * A person, by id, which redirects to their profile.
     */
    public static function person(int $personId): string {
        return WEBPATH . 'mp/?pid=' . $personId;
    }

    /**
     * How a member voted on key issues, at They Vote For You. It takes the member id of one term.
     */
    public static function theyVoteForYou(int $memberId): string {
        return 'https://theyvoteforyou.org.au/mp.php?id=uk.org.publicwhip/member/' . $memberId;
    }

    /**
     * Signing up to be emailed when a person speaks.
     */
    public static function alert(int $personId): string {
        return WEBPATH . 'alert/?only=1&pid=' . $personId;
    }

    /**
     * Everything a person has said, newest first.
     */
    public static function speeches(int $personId): string {
        return WEBPATH . 'search/?' . http_build_query(['pid' => $personId, 'pop' => 1]);
    }

}
