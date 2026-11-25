<?php

/**
 * @file
 * Capturing the legacy header and footer. A stand-in PAGE buffers output as the real one does.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/data.php';

use OpenAustralia\TWFY\Http\LegacyLayout;

beforeEach(function () {
    $this->globals = [$GLOBALS['PAGE'] ?? null, $GLOBALS['DATA'] ?? null];
    $GLOBALS['DATA'] = new DATA();
    $GLOBALS['PAGE'] = new class() {

        public function page_start(): void {
            ob_start();
            echo '[start]';
        }

        public function page_end(): void {
            echo '[end]';
            ob_end_flush();
        }

    };
});

afterEach(function () {
    [$GLOBALS['PAGE'], $GLOBALS['DATA']] = $this->globals;
});

describe('LegacyLayout::top()', function () {
    it('returns the header', function () {
        expect((new LegacyLayout())->top('mps'))->toBe('[start]');
    });

    it('leaves no buffer open', function () {
        $level = ob_get_level();
        (new LegacyLayout())->top('mps');

        expect(ob_get_level())->toBe($level);
    });

    it('gives the page a viewport', function () {
        (new LegacyLayout())->top('mps');

        expect($GLOBALS['DATA']->page_metadata('mps', 'responsive'))->toBeTrue();
    });

    it('sets the page key for the menu', function () {
        (new LegacyLayout())->top('peers');

        expect($GLOBALS['this_page'])->toBe('peers');
    });
});

describe('LegacyLayout::bottom()', function () {
    it('returns the footer', function () {
        expect((new LegacyLayout())->bottom())->toBe('[end]');
    });

    it('leaves no buffer open', function () {
        $level = ob_get_level();
        (new LegacyLayout())->bottom();

        expect(ob_get_level())->toBe($level);
    });
});
