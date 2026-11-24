<?php

/**
 * @file
 * The periods the admin usage page offers.
 */

require_once __DIR__ . '/../../../bootstrap.php';

use OpenAustralia\TWFY\Admin\UsageController;

describe('UsageController::period()', function () {
    it('accepts an offered period', function (string $requested, int $period) {
        expect(UsageController::period($requested))->toBe($period);
    })->with([['30', 30], ['7', 7], ['1', 1]]);

    it('falls back to 30 days', function (string $requested) {
        expect(UsageController::period($requested))->toBe(30);
    })->with(['', '2', 'banana']);
});

describe('UsageController::range()', function () {
    it('ends today', function () {
        expect(UsageController::range(7, new DateTimeImmutable('2026-03-10'))[1])->toBe('2026-03-10');
    });

    it('starts period - 1 days before', function () {
        expect(UsageController::range(7, new DateTimeImmutable('2026-03-10'))[0])->toBe('2026-03-04');
    });

    describe('for today only', function () {
        it('starts today', function () {
            expect(UsageController::range(1, new DateTimeImmutable('2026-03-10'))[0])->toBe('2026-03-10');
        });
    });
});
