<?php

/**
 * @file
 */

require_once __DIR__ . '/../../www/includes/postcode.php';

describe('canonicalise_postcode()', function () {
    describe('with a canonical postcode', function () {
        it('returns it unchanged', function (string $postcode) {
            expect(canonicalise_postcode($postcode))->toBe($postcode);
        })->with([
            'one digit' => '1',
            'two digits' => '12',
            'three digits' => '123',
            'four digits' => '2000',
            'Darwin' => '800',
            'Melbourne' => '3000',
            'Brisbane' => '4000',
            'Perth' => '6000',
            'Adelaide' => '5000',
            'Hobart' => '7000',
        ]);
    });

    describe('with surrounding whitespace', function () {
        it('trims it', function () {
            expect(canonicalise_postcode('  2000  '))->toBe('2000');
        });
    });
});
