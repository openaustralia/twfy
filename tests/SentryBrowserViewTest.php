<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/SentryBrowserView.php';

/**
 * SentryBrowserView (see www/includes/easyparliament/page.php's page_header()) is
 * pure decision logic for the browser-side Sentry Loader Script - no echo, no
 * globals, so it's directly testable. See that class's own comment for why DEVSITE/
 * SENTRY_BROWSER_DSN have to be passed in as plain parameters rather than read
 * directly for this to be possible at all.
 */
class SentryBrowserViewTest extends TestCase {

    /**
     *
     */
    public function test_loaderScriptUrl_returns_null_on_a_dev_site_even_with_a_dsn() {
        $url = SentryBrowserView::loaderScriptUrl(true, 'https://publickey123@o12345.ingest.sentry.io/67890');

        $this->assertNull($url);
    }

    /**
     *
     */
    public function test_loaderScriptUrl_returns_null_when_there_is_no_dsn() {
        $this->assertNull(SentryBrowserView::loaderScriptUrl(false, null));
        $this->assertNull(SentryBrowserView::loaderScriptUrl(false, ''));
    }

    /**
     * A DSN with no "user" component at all (parse_url() can't find a public key in
     * it) - shouldn't happen with a real Sentry-issued DSN, but shouldn't crash or
     * emit a broken <script src> either.
     */
    public function test_loaderScriptUrl_returns_null_for_a_dsn_with_no_public_key() {
        $url = SentryBrowserView::loaderScriptUrl(false, 'https://o12345.ingest.sentry.io/67890');

        $this->assertNull($url);
    }

    /**
     * The one branch an inline check against the real DEVSITE/SENTRY_BROWSER_DSN
     * constants could never reach in a test (see this class's own comment) - not
     * dev, DSN present and well-formed.
     */
    public function test_loaderScriptUrl_builds_the_loader_url_from_the_dsns_public_key() {
        $url = SentryBrowserView::loaderScriptUrl(false, 'https://publickey123@o12345.ingest.sentry.io/67890');

        $this->assertSame('https://js.sentry-cdn.com/publickey123.min.js', $url);
    }

    /**
     * The public key ends up in a URL path segment - url-encoded, same as
     * page.php's own urlencode() did before this logic moved here, in case a key
     * ever contains characters that need it (unlikely for a Sentry-issued one, but
     * cheap insurance against a malformed src="" attribute). "+" is valid unencoded
     * in a URL's userinfo component (so parse_url() accepts it in the DSN as-is),
     * but means a literal space if left unencoded in a path segment - a real
     * character to actually check the encoding step against, not a contrived one.
     */
    public function test_loaderScriptUrl_url_encodes_the_public_key() {
        $url = SentryBrowserView::loaderScriptUrl(false, 'https://key+plus@o12345.ingest.sentry.io/67890');

        $this->assertSame('https://js.sentry-cdn.com/key%2Bplus.min.js', $url);
    }

    /**
     *
     */
    public function test_renderTag_returns_an_empty_string_when_nothing_should_render() {
        $this->assertSame('', SentryBrowserView::renderTag(true, 'https://publickey123@o12345.ingest.sentry.io/67890', 'production'));
        $this->assertSame('', SentryBrowserView::renderTag(false, null, 'production'));
    }

    /**
     * page.php's own glue is just `echo SentryBrowserView::renderTag(...)` now - this
     * is the one test that actually exercises the branch a real page render could
     * never reach (see this class's own file comment), so it's worth checking the
     * full markup precisely, not just "isn't empty".
     */
    public function test_renderTag_renders_both_script_tags_with_the_loader_url_and_environment() {
        $tag = SentryBrowserView::renderTag(false, 'https://publickey123@o12345.ingest.sentry.io/67890', 'production');

        $this->assertStringContainsString('window.sentryOnLoad', $tag);
        $this->assertStringContainsString('environment: "production"', $tag);
        $this->assertStringContainsString('src="https://js.sentry-cdn.com/publickey123.min.js" crossorigin="anonymous"', $tag);
    }

    /**
     * $environment reaches a JS string literal, not an HTML attribute - addslashes(),
     * not htmlspecialchars(), same as page.php's own pre-extraction code did (it's a
     * fixed SENTRY_ENVIRONMENT constant in practice, never untrusted input, but the
     * escaping should still match the context it's actually used in).
     */
    public function test_renderTag_escapes_the_environment_for_a_js_string_literal() {
        $tag = SentryBrowserView::renderTag(false, 'https://publickey123@o12345.ingest.sentry.io/67890', "it's fine");

        $this->assertStringContainsString('environment: "it\\\'s fine"', $tag);
    }

}
