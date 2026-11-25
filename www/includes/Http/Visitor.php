<?php

namespace OpenAustralia\TWFY\Http;

use Illuminate\Support\Facades\Cookie;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * What the member pages know about whoever is visiting. The legacy THEUSER says who they are.
 */
class Visitor {

    private const COOKIE_MINUTES = 7 * 24 * 60;

    /**
     * The electorate they have given, or an empty string.
     */
    public function electorate(): string {
        return (string) $GLOBALS['THEUSER']->constituency();
    }

    /**
     * The current or latest member for their electorate, in either house or one, if they have given an electorate.
     */
    public function member(?House $house = null): ?Member {
        $electorate = $this->electorate();

        return $electorate === '' ? null : Member::latestInElectorate($electorate, $house);
    }

    /**
     * Keeps their electorate for next time: in their account if signed in, else in a cookie on the response.
     */
    public function rememberElectorate(string $electorate, Response $response): Response {
        $user = $GLOBALS['THEUSER'];
        if ($user->isloggedin()) {
            User::query()->whereKey($user->user_id())->update(['constituency' => $electorate]);
        } else {
            // THEUSER reads this cookie as it is, so it isn't encrypted.
            $response->headers->setCookie(
                Cookie::make(CONSTITUENCY_COOKIE, $electorate, self::COOKIE_MINUTES, '/', COOKIEDOMAIN, false, false)
            );
        }
        return $response;
    }

}
