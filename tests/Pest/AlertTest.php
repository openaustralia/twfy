<?php

/**
 * @file
 */

defined('WEBPATH') || define('WEBPATH', '/');

/**
 * What alert_confirmation_advert() printed and returned.
 *
 * @param array<string, mixed> $details
 *   The alert's details.
 *
 * @return array{string, string}
 *   The identifier it returned, then the HTML it printed.
 */
function advert_for(array $details): array {
    ob_start();
    $identifier = alert_confirmation_advert($details);
    return [$identifier, ob_get_clean()];
}

describe('alert_details_to_criteria()', function () {
    it('joins what was given', function (string $keyword, string $pid, string $expected) {
        expect(alert_details_to_criteria(['keyword' => $keyword, 'pid' => $pid]))->toBe($expected);
    })->with([
        'a keyword alone' => ['climate change', '', 'climate change'],
        'a numeric keyword' => ['2024', '', '2024'],
        'a keyword with an apostrophe' => ["children's education", '', "children's education"],
        'a keyword with spaces around it' => ['  budget  ', '', '  budget  '],
        'a speaker alone' => ['', '12345', 'speaker:12345'],
        'a keyword then a speaker' => ['budget', '54321', 'budget speaker:54321'],
        'a phrase then a speaker' => ['renewable energy', '11111', 'renewable energy speaker:11111'],
        'nothing' => ['', '', ''],
    ]);
});

describe('alert_confirmation_advert()', function () {
    describe('with a speaker', function () {
        it('returns the word advert identifier', function () {
            expect(advert_for(['pid' => '12345'])[0])->toBe('twfy-alert-word');
        });

        it('prints a link to the alert sign-up', function () {
            expect(advert_for(['pid' => '12345'])[1])->toContain('<a href="/alert/">');
        });
    });

    describe('without a speaker', function () {
        it('returns the person advert identifier', function () {
            expect(advert_for(['pid' => ''])[0])->toBe('twfy-alert-person');
        });

        it('prints a link to the alert sign-up', function () {
            expect(advert_for(['pid' => ''])[1])->toContain('<a href="/alert/">');
        });
    });
});

describe('new ALERT()', function () {
    it('starts with an empty property', function (string $property) {
        expect((new ALERT())->$property)->toBe('');
    })->with(['alert_id', 'email', 'criteria']);
});
