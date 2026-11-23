<?php

/**
 * @file
 * The viewport meta tag PAGE::page_header() emits for pages marked 'responsive' in metadata.php.
 */

require_once __DIR__ . '/../bootstrap.php';
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
 * Renders page_header() for the home page and returns its HTML, using $data if given.
 * Home is the one page that doesn't build a URL for its "start" link.
 */
function render_page_header(?DATA $data = null): string {
    global $this_page, $DATA;
    $this_page = 'home';
    $DATA = $data ?? new DATA();

    ob_start();
    (new PAGE())->page_header();
    return ob_get_clean();
}

describe('PAGE::page_header()', function () {
    describe('for a page marked responsive', function () {
        beforeEach(function () {
            $this->html = render_page_header();
        });

        it('sets the viewport to the device width', function () {
            expect($this->html)->toContain('<meta name="viewport" content="width=device-width, initial-scale=1">');
        });

        it('leaves pinch zoom enabled', function () {
            expect($this->html)->not->toContain('maximum-scale');
        });
    });

    describe('for a page not marked responsive', function () {
        it('omits the viewport meta tag', function () {
            $withoutKey = new class extends DATA {

                public function page_metadata($page, $key) {
                    return $key === 'responsive' ? false : parent::page_metadata($page, $key);
                }

            };

            expect(render_page_header($withoutKey))->not->toContain('name="viewport"');
        });
    });
});
