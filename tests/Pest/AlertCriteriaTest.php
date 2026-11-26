<?php

/**
 * @file
 * What an alert's criteria say, and where its search is.
 */

use OpenAustralia\TWFY\Models\Alert;

describe('Alert', function () {
    describe('personId()', function () {
        it('finds the person', function (string $criteria, ?int $expected) {
            expect((new Alert(['criteria' => $criteria]))->personId())->toBe($expected);
        })->with([
            'a person alone' => ['speaker:10001', 10001],
            'a person and a word' => ['speaker:10001 climate', 10001],
            'a word and a person' => ['climate speaker:10001', 10001],
            'a word alone' => ['climate', null],
            'a word that mentions speakers' => ['speakers', null],
        ]);
    });

    describe('keywords()', function () {
        it('leaves out the person', function (string $criteria, string $expected) {
            expect((new Alert(['criteria' => $criteria]))->keywords())->toBe($expected);
        })->with([
            'a person alone' => ['speaker:10001', ''],
            'a person and a word' => ['speaker:10001 climate', 'climate'],
            'a word and a person' => ['climate speaker:10001', 'climate'],
            'a phrase' => ['climate change', 'climate change'],
        ]);
    });

    describe('searchPath()', function () {
        it('searches for the person and the words', function (string $criteria, string $expected) {
            expect((new Alert(['criteria' => $criteria]))->searchPath())->toBe($expected);
        })->with([
            'a person alone' => ['speaker:10001', '/search/?pid=10001'],
            'a word alone' => ['climate', '/search/?s=climate'],
            'a person and words' => ['speaker:10001 climate change', '/search/?pid=10001&s=climate+change'],
            'words that need escaping' => ['a&b=c', '/search/?s=a%26b%3Dc'],
        ]);
    });
});
