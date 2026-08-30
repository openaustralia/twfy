<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/PlausibleView.php';

/**
 * PlausibleView (see www/includes/easyparliament/page.php's page_header()/
 * page_header_mobile()) is pure decision logic for the Plausible.io analytics tag -
 * no echo, no globals, so it's directly testable. See that class's own comment for
 * why DEVSITE/PLAUSIBLE_SCRIPT_ID have to be passed in as plain parameters rather
 * than read directly for this to be possible at all.
 */
class PlausibleViewTest extends TestCase {

    /**
     *
     */
    public function test_renderTag_returns_an_empty_string_on_a_dev_site_even_with_a_script_id() {
        $this->assertSame('', PlausibleView::renderTag(true, 'pa-eMxq8hHU91Qjn6D5vwWZa'));
    }

    /**
     *
     */
    public function test_renderTag_returns_an_empty_string_when_there_is_no_script_id() {
        $this->assertSame('', PlausibleView::renderTag(false, ''));
    }

    /**
     * The one branch an inline check against the real DEVSITE/PLAUSIBLE_SCRIPT_ID
     * constants could never reach in a test (see this class's own comment) - not
     * dev, script id present.
     */
    public function test_renderTag_renders_the_script_src_and_init_call() {
        $tag = PlausibleView::renderTag(false, 'pa-eMxq8hHU91Qjn6D5vwWZa');

        $this->assertStringContainsString('src="https://plausible.io/js/pa-eMxq8hHU91Qjn6D5vwWZa.js"', $tag);
        $this->assertStringContainsString('plausible.init()', $tag);
    }

    /**
     * $scriptId ends up in an HTML attribute - htmlspecialchars(), same convention
     * as SentryBrowserView::loaderScriptUrl()'s own src="" handling, in case it
     * ever contains characters that would break out of the attribute (unlikely for
     * a Plausible-issued id, but cheap insurance).
     */
    public function test_renderTag_escapes_the_script_id_for_the_html_attribute() {
        $tag = PlausibleView::renderTag(false, 'pa-"><script>alert(1)</script>');

        $this->assertStringNotContainsString('"><script>alert(1)</script>', $tag);
        $this->assertStringContainsString('&quot;&gt;', $tag);
    }

}
