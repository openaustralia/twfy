<?php

namespace OpenAustralia\TWFY\Parliament;

/**
 * The links elsewhere that a profile offers.
 */
final class MemberLinks {

    private const ELECTION_YEARS = [2022, 2019, 2016, 2013, 2010, 2007];

    /**
     * The links there is an address for.
     *
     * @return list<array{url: string, text: string, source: string|null}>
     *   In the order the page shows them. The source says whose site a link leads to.
     */
    public static function for(MemberProfile $profile, MemberFacts $info): array {
        $term = $profile->person->latest();
        $name = $term->full_name;
        $links = [];
        $add = function (?string $url, string $text, ?string $source = null) use (&$links): void {
            if ($url) {
                $links[] = ['url' => $url, 'text' => $text, 'source' => $source];
            }
        };

        $email = $info->get('mp_email');
        if ($email) {
            $add('mailto:' . $email, "Email $name");
        } else {
            $add($info->get('mp_contact_form'), 'Contact form', 'On the Australian Parliament website');
        }
        $add($info->get('mp_twitter_url'), "$name on Twitter");
        $add($info->get('mp_facebook_url'), "$name on Facebook");
        $add($info->get('mp_website'), "$name's personal website");
        $add($info->get('aph_url'), "Parliament House web page for $name");
        $add($info->get('wikipedia_url'), 'Biography', 'From Wikipedia');
        $add($info->get('mp_biography_qanda'), 'Biography', "From ABC's Q & A");
        foreach (self::ELECTION_YEARS as $year) {
            $add($info->get("abc_election_results_$year"), "$year Election results for $term->constituency", 'From ABC');
        }

        return $links;
    }

}
