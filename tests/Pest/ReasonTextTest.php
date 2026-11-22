<?php

/**
 * @file
 * Shortening API key reasons for the admin usage page.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\UsageStats\ReasonText;

describe('ReasonText::shorten()', function () {
    it('keeps a short reason', function () {
        expect(ReasonText::shorten('A school project'))->toBe('A school project');
    });

    it('collapses whitespace', function () {
        expect(ReasonText::shorten("A school\n\n  project "))->toBe('A school project');
    });

    describe('with a long reason', function () {
        beforeEach(function () {
            $this->short = ReasonText::shorten(str_repeat('Tracking how each senator votes, ', 4));
        });

        it('fits in 65 characters', function () {
            expect(mb_strlen($this->short))->toBeLessThanOrEqual(65);
        });

        it('cuts at a word boundary', function () {
            expect($this->short)->toBe('Tracking how each senator votes, Tracking how each senator...');
        });
    });

    it('counts characters, not bytes', function () {
        expect(ReasonText::shorten(str_repeat('é', 65)))->toBe(str_repeat('é', 65));
    });
});
