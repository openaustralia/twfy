<?php

/**
 * @file
 * Whether/how to render the browser-side Sentry Loader Script (page.php's
 * page_header()) - pure decision logic, no echo of its own, so it's directly
 * testable without $PAGE/$DATA or a full page render. Same split as
 * FrontPageView.php/HansardSpeechView.php.
 *
 * Extracted rather than left as an inline `if` in page.php for a reason beyond
 * tidiness: DEVSITE/SENTRY_BROWSER_DSN are define()'d once from conf/general at
 * bootstrap and can't be toggled per-test, so a test can only ever exercise the
 * "disabled" branch of an inline check against the real constants - the "renders"
 * branch is only reachable at all by taking these as plain parameters instead.
 */

/**
 *
 */
class SentryBrowserView {

    /**
     * The Sentry Loader Script's src URL, built from the browser DSN's public key -
     * or null when nothing should render at all: on a dev site, when the DSN isn't
     * configured yet (empty string - see conf/general-example.local-dev, safe
     * default until openaustralia/infrastructure#716's sentry_browser_public_key is
     * actually set), or if it's malformed enough that parse_url() can't find a
     * user/public-key component in it.
     */
    public static function loaderScriptUrl(bool $devsite, ?string $dsn): ?string {
        if ($devsite || !$dsn) {
            return null;
        }

        $publicKey = parse_url($dsn, PHP_URL_USER);
        if (!$publicKey) {
            return null;
        }

        return 'https://js.sentry-cdn.com/' . urlencode($publicKey) . '.min.js';
    }

}
