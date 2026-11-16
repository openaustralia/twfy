<?php

/**
 * @file
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\AlertMaxAge;

// The mailer does not email items from debates more than 8 weeks old.
describe('AlertMaxAge::isWithinMaxAge()', function () {
    it('judges a debate against today', function (string $debate, bool $expected) {
        expect(AlertMaxAge::isWithinMaxAge($debate, '2026-10-15'))->toBe($expected);
    })->with([
        'from today' => ['2026-10-15', true],
        'exactly 8 weeks old' => ['2026-08-20', true],
        'a day over 8 weeks old' => ['2026-08-19', false],
    ]);

    describe('without a date for today', function () {
        it('emails a debate from today', function () {
            expect(AlertMaxAge::isWithinMaxAge(date('Y-m-d')))->toBeTrue();
        });

        it('skips a debate from 9 weeks ago', function () {
            expect(AlertMaxAge::isWithinMaxAge(date('Y-m-d', strtotime('-9 weeks'))))->toBeFalse();
        });
    });
});
