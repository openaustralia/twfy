<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Sends someone who has just signed in back to the page they came from.
 */
class LoginResponse implements LoginResponseContract {

    /**
     * Whether a URL stays on this site.
     *
     * Only site-relative URLs are safe. Protocol-relative ones (//evil.com) are rejected because browsers treat
     * them as absolute. So are backslashes and control characters, which browsers turn into slashes or drop, so
     * that /\evil.com and "/<tab>/evil.com" become //evil.com.
     */
    public static function isSafeRedirectUrl(string $url): bool {
        return str_starts_with($url, '/') && !str_starts_with($url, '//') && !preg_match('/[\\\\\x00-\x1f\x7f]/', $url);
    }

    /**
     * The page to send someone to: the request's "ret" if it stays on this site, else the home page.
     */
    public static function destination(Request $request): string {
        $ret = (string) $request->input('ret');

        if ($ret !== '' && self::isSafeRedirectUrl($ret)) {
            return $ret;
        }

        return (string) config('fortify.home');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *   The login request.
     */
    public function toResponse($request): RedirectResponse {
        // away(), as to() would trim the trailing slash the legacy URLs end in.
        return redirect()->away(self::destination($request));
    }

}
