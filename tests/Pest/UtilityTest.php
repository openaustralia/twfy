<?php

/**
 * @file
 */

use League\Plates\Engine;

require_once __DIR__ . '/../../www/includes/utility.php';

describe('get_plates_engine()', function () {
    it('returns a Plates engine', function () {
        expect(get_plates_engine())->toBeInstanceOf(Engine::class);
    });

    // page.php's footer render and a page-specific template both call this in
    // the same request, so sharing one instance is the point, not a detail.
    describe('when called again', function () {
        it('returns the same instance', function () {
            expect(get_plates_engine())->toBe(get_plates_engine());
        });
    });
});
