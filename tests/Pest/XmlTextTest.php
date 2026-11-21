<?php

/**
 * @file
 * The text helpers the PHP loader shares with the Perl one's behaviour. Uses invented values.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\XmlImport\XmlText;

describe('XmlText::canonTime()', function () {
    it('gives a time as HH:MM:SS', function (string $time, string $expected) {
        expect(XmlText::canonTime($time))->toBe($expected);
    })->with([
        'with seconds' => ['10:05:30', '10:05:30'],
        'without seconds' => ['10:05', '10:05:00'],
        'one digit hour' => ['9:05', '09:05:00'],
        'leading zero' => ['010:05', '10:05:00'],
        'past midnight' => ['24:30', '24:30:00'],
    ]);

    it('finds the time in the speaker line', function (string $text, string $expected) {
        expect(XmlText::canonTime($text))->toBe($expected);
    })->with([
        'after a name' => [" (Example) (16:01):", '16:01:00'],
        'with an apostrophe' => [" (O'Example) (02:11):", '02:11:00'],
        'with no name' => [' (17:03):', '17:03:00'],
        'cut short' => ['17:03):', '17:03:00'],
        'with a dash in the name' => [' (Example—Minister for Examples) (15:03):', '15:03:00'],
    ]);

    it('gives null when there is no time', function (string $text) {
        expect(XmlText::canonTime($text))->toBeNull();
    })->with([
        'unknown' => ['unknown'],
        'empty' => [''],
        'hour only' => ['16:'],
        'minutes that are not minutes' => ['25:99'],
        'words only' => [' (Example):'],
    ]);
});
