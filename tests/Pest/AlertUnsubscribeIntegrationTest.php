<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\Models\Alert as AlertModel;

uses(TransactionalTestCase::class);

defined('DOMAIN') || define('DOMAIN', 'example.org');
defined('WEBPATH') || define('WEBPATH', '/');

/**
 * Whether an alert has been removed.
 *
 * @param int $id
 *   The alert's id.
 *
 * @return bool
 *   True if the alert is deleted.
 */
function alert_is_removed(int $id): bool {
    return AlertModel::findOrFail($id)->deleted;
}

// One-click unsubscribe: every alert at an address, authorised by the token of
// one of them, so no login is needed.
describe('ALERT::delete_all_at_address()', function () {
    describe('with the token of one alert', function () {
        beforeEach(function () {
            $this->a = AlertFixtures::alert('alice@example.invalid', 'speaker:1', true, false, 'tokena')->alert_id;
            $this->b = AlertFixtures::alert('alice@example.invalid', 'climate', true, false, 'tokenb')->alert_id;
            $this->c = AlertFixtures::alert('Alice@Example.invalid', 'budget', true, false, 'tokenc')->alert_id;
            $this->bob = AlertFixtures::alert('bob@example.invalid', 'speaker:1', true, false, 'tokenbob')->alert_id;
            $this->removed = (new ALERT())->delete_all_at_address($this->a . '-tokena');
        });

        it('counts every alert at the address', function () {
            expect($this->removed)->toBe(3);
        });

        it('removes the alert the token belongs to', function () {
            expect(alert_is_removed($this->a))->toBeTrue();
        });

        it('removes the others at the address', function () {
            expect(alert_is_removed($this->b))->toBeTrue();
        });

        it('ignores the case of the address', function () {
            expect(alert_is_removed($this->c))->toBeTrue();
        });

        it('leaves other addresses alone', function () {
            expect(alert_is_removed($this->bob))->toBeFalse();
        });
    });

    describe('with a wrong or malformed token', function () {
        beforeEach(function () {
            $this->a = AlertFixtures::alert('alice@example.invalid', 'speaker:1', true, false, 'tokena')->alert_id;
        });

        it('refuses it', function (string $token) {
            expect((new ALERT())->delete_all_at_address(str_replace('{id}', (string) $this->a, $token)))->toBeFalse();
        })->with([
            'a wrong secret' => '{id}-wrongtoken',
            'no id' => 'nonsense',
            'nothing' => '',
            'no secret' => '{id}-',
            'an unknown alert' => '999999-tokena',
            'no alert id' => '-tokena',
        ]);

        it('removes nothing', function () {
            (new ALERT())->delete_all_at_address($this->a . '-wrongtoken');

            expect(alert_is_removed($this->a))->toBeFalse();
        });
    });

    // get_http_var() returns an array for ?t[]=x, which used to throw a
    // TypeError instead of being refused.
    describe('with a token that is not a string', function () {
        beforeEach(function () {
            AlertFixtures::alert('alice@example.invalid', 'speaker:1', true, false, 'tokena');
        });

        it('refuses it', function (mixed $token) {
            expect((new ALERT())->delete_all_at_address($token))->toBeFalse();
        })->with(['an array' => [['bad']], 'null' => [null], 'a number' => [12], 'false' => [false]]);
    });

    describe('when an alert was already removed', function () {
        it('does not count it again', function () {
            $a = AlertFixtures::alert('alice@example.invalid', 'speaker:1', true, false, 'tokena')->alert_id;
            AlertFixtures::alert('alice@example.invalid', 'climate', true, true, 'tokenb');

            expect((new ALERT())->delete_all_at_address($a . '-tokena'))->toBe(1);
        });
    });

    // Someone who unsubscribes twice, or whose mail app retries, still gets a
    // success, because the alert exists and the token is right.
    describe('when repeated', function () {
        it('removes nothing more, without failing', function () {
            $a = AlertFixtures::alert('alice@example.invalid', 'speaker:1', true, false, 'tokena')->alert_id;
            (new ALERT())->delete_all_at_address($a . '-tokena');

            expect((new ALERT())->delete_all_at_address($a . '-tokena'))->toBe(0);
        });
    });
});

describe('ALERT::email_for_token()', function () {
    it('refuses a token that is not a string', function (mixed $token) {
        expect((new ALERT())->email_for_token($token))->toBeNull();
    })->with(['an array' => [['bad']], 'null' => [null], 'a number' => [12], 'false' => [false]]);
});

describe('ALERT::unsubscribe_url()', function () {
    it('is an https link', function () {
        expect(ALERT::unsubscribe_url(12, 'abc123'))->toStartWith('https://');
    });

    it('carries the alert id and token', function () {
        expect(ALERT::unsubscribe_url(12, 'abc123'))->toContain('alert/unsubscribe/?t=12-abc123');
    });
});
