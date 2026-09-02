<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/data.php';
require_once __DIR__ . '/../www/includes/easyparliament/skin.php';
require_once __DIR__ . '/../www/includes/easyparliament/page.php';

// utility.php can't be required here - it redeclares twfy_debug(), already
// stubbed in bootstrap.php for the rest of the unit suite. get_http_var()
// (SKIN's constructor calls it) is the one function from there page_header()
// actually needs, so it's stubbed the same way bootstrap.php stubs the rest.
if (!function_exists('get_http_var')) {

    function get_http_var($name, $default = '') {
        return $_GET[$name] ?? $_POST[$name] ?? $default;
    }

}

if (!defined('DEVSITE')) {
    define('DEVSITE', false);
}
if (!defined('SENTRY_DSN')) {
    define('SENTRY_DSN', 'https://examplepublickey@o0.ingest.sentry.io/0');
}
if (!defined('SENTRY_ENVIRONMENT')) {
    define('SENTRY_ENVIRONMENT', 'test');
}
if (!defined('CONTACTEMAIL')) {
    define('CONTACTEMAIL', 'contact@example.com');
}
if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.com');
}

/**
 * page_header()'s browser-Sentry call site can't be covered by
 * PageRenderingIntegrationTestCase's subprocess-based tests (eg
 * IndexPageIntegrationTest) - those render the page with proc_open in a
 * separate PHP process, and code coverage never crosses that boundary back
 * to PHPUnit. DATA and SKIN are both file-based with no DB dependency, so
 * PAGE::page_header() can be called directly here instead, in-process,
 * where coverage actually gets recorded. DEVSITE/SENTRY_DSN/
 * SENTRY_ENVIRONMENT are real constants here (not parameters, unlike
 * SentryBrowserView::renderTag() itself - see that class's own comment),
 * so this only exercises one branch; that's fine, since renderTag()'s own
 * branches are already fully covered in SentryBrowserViewTest.php.
 */
class PageHeaderAnalyticsTest extends TestCase {

    /**
     *
     */
    public function test_page_header_includes_the_sentry_loader_script() {
        global $this_page, $DATA;
        $this_page = 'home';
        $DATA = new DATA();

        $page = new PAGE();
        ob_start();
        $page->page_header();
        $html = ob_get_clean();

        $this->assertStringContainsString('Sentry.init(', $html);
        $this->assertStringContainsString('examplepublickey', $html);
    }

}
