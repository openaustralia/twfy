<?php

/**
 * @file
 * SKIN::output_stylesheets() - no DB, no $PAGE, just the stylesheet <link> tags for
 * whichever skin is selected. `new SKIN()` itself is harmless (its constructor's
 * $this_page/cookie branches just resolve to the default skin here) - $skin is
 * overwritten directly afterwards, bypassing set_skin()'s validation, so the test
 * controls the skin outright.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/skin.php';

// Same stub as PageHeaderAnalyticsTest.php, for the same reason - utility.php
// can't be required here without redeclaring twfy_debug(), already stubbed in
// bootstrap.php. output_stylesheets()'s channel4 branch is the one caller here.
if (!function_exists('get_http_var')) {

    function get_http_var($name, $default = '') {
        return $_GET[$name] ?? $_POST[$name] ?? $default;
    }

}

/**
 *
 */
class SkinTest extends TestCase {

    /**
     *
     */
    public function test_output_stylesheets_includes_the_mobile_stylesheets_for_the_mobile_skin() {
        $skin = new SKIN();
        $skin->skin = 'mobile';

        ob_start();
        $skin->output_stylesheets();
        $html = ob_get_clean();

        $this->assertStringContainsString('layout_mobile.css?v=2', $html);
        $this->assertStringContainsString('mobile.css?v=3', $html);
    }

}
