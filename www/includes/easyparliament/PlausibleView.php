<?php

/**
 * @file
 * Whether/how to render the Plausible.io analytics tag (page.php's page_header()/
 * page_header_mobile()) - pure logic and markup building, no echo of its own, so
 * it's directly testable without $PAGE/$DATA or a full page render. Same split as
 * SentryBrowserView.php (page.php's own browser error tracking) - see that file's
 * own comment for why DEVSITE/PLAUSIBLE_SCRIPT_ID have to reach here as plain
 * parameters rather than being read directly: they're define()'d once from
 * conf/general at bootstrap and can't be toggled per-test.
 *
 * Plausible Cloud (not the self-hosted option - see openaustralia/infrastructure#547,
 * which removed a self-hosted module that was never actually used) needs nothing
 * secret to embed. $scriptId is the site-specific fragment of the script URL
 * Plausible's own dashboard generates (eg "pa-eMxq8hHU91Qjn6D5vwWZa" from
 * "https://plausible.io/js/pa-eMxq8hHU91Qjn6D5vwWZa.js") - it identifies which
 * site's dashboard events land in, same "public, not a secret" status as Sentry's
 * DSN public key, since it's already embedded in every page's own HTML/script src.
 */

/**
 *
 */
class PlausibleView {

    /**
     * The complete <script> tags for page_header()'s/page_header_mobile()'s
     * <head> - empty string on a dev site or when PLAUSIBLE_SCRIPT_ID isn't
     * configured yet (empty string - safe default until a human registers the
     * site in Plausible's dashboard and sets openaustralia/infrastructure's
     * plausible_script_id).
     */
    public static function renderTag(bool $devsite, string $scriptId): string {
        if ($devsite || !$scriptId) {
            return '';
        }

        return '<script async src="https://plausible.io/js/' . htmlspecialchars($scriptId) . '.js"></script>'
            . '<script>'
            . 'window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};'
            . 'plausible.init()'
            . '</script>';
    }

}
