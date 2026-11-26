<?php

/**
 * @file
 * The page title as PAGE::page_header() repeats it in the meta keywords.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/UrlStandIn.php';
require_once __DIR__ . '/../../www/includes/data.php';
require_once __DIR__ . '/../../www/includes/easyparliament/skin.php';
require_once __DIR__ . '/../../www/includes/easyparliament/page.php';
require_once __DIR__ . '/../../www/includes/SentryBrowserScript.php';

// utility.php can't be required here (it redeclares twfy_debug()), so stub what page_header() calls.
if (!function_exists('get_http_var')) {

    function get_http_var($name, $default = '') {
        return $_GET[$name] ?? $_POST[$name] ?? $default;
    }

}

foreach (['DEVSITE' => false, 'PLAUSIBLE_SCRIPT_ID' => 'pa-test123', 'CONTACTEMAIL' => 'contact@example.com', 'DOMAIN' => 'example.com'] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}

/**
 * The meta keywords of a Senator's page with a subtitle.
 */
function keywords_for_subtitle(string $subtitle): string {
    global $this_page, $DATA;
    $this_page = 'peer';
    $DATA = new DATA();
    $DATA->set_page_metadata('peer', 'subtitle', $subtitle);

    ob_start();
    (new PAGE())->page_header();
    preg_match('/<meta name="keywords"\s+content="([^"]*)"/s', (string) ob_get_clean(), $matches);

    return $matches[1] ?? '';
}

describe('PAGE::page_header() keywords', function () {
    describe('with an already escaped title', function () {
        it('escapes it only once', function () {
            expect(keywords_for_subtitle('Kim O&#039;Specimen'))
              ->toContain('Kim O&#039;Specimen')
              ->not->toContain('&amp;#039;');
        });
    });

    describe('with markup in the title', function () {
        it('escapes it', function () {
            expect(keywords_for_subtitle('<b>Kim</b>'))->toContain('&lt;b&gt;Kim&lt;/b&gt;');
        });
    });
});
