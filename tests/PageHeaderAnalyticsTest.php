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
if (!defined('PLAUSIBLE_SCRIPT_ID')) {
    define('PLAUSIBLE_SCRIPT_ID', 'pa-test123');
}
if (!defined('CONTACTEMAIL')) {
    define('CONTACTEMAIL', 'contact@example.com');
}
if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.com');
}

/**
 * page_header()'s analytics_tag() call site can't be covered by
 * PageRenderingIntegrationTestCase's subprocess-based tests (eg
 * IndexPageIntegrationTest) - those render the page with proc_open in a
 * separate PHP process, and code coverage never crosses that boundary back
 * to PHPUnit. DATA and SKIN are both file-based with no DB dependency, so
 * PAGE::page_header() can be called directly here instead, in-process,
 * where coverage actually gets recorded. DEVSITE/PLAUSIBLE_SCRIPT_ID are
 * real constants here (not parameters, unlike PlausibleView::renderTag()
 * itself - see PlausibleViewTest.php for why), so this only exercises one
 * branch; that's fine, since renderTag()'s own branches are already fully
 * covered there.
 */
class PageHeaderAnalyticsTest extends TestCase {

    /**
     *
     */
    public function test_page_header_includes_the_plausible_tag() {
        global $this_page, $DATA;
        $this_page = 'home';
        $DATA = new DATA();

        $page = new PAGE();
        ob_start();
        $page->page_header();
        $html = ob_get_clean();

        $this->assertStringContainsString('plausible.io/js/pa-test123.js', $html);
    }

}
