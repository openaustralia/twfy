<?php

/**
 * @file
 * Signing in from the epuser_id cookie. Uses a fictional Superuser.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * The signed-in user for a cookie value.
 */
function the_user_spec_for(string $cookie): THEUSER {
    $_COOKIE = ['epuser_id' => $cookie];
    return new THEUSER();
}

beforeEach(function () {
    $this->saved = $_COOKIE;
    $this->boss = User::create(['firstname' => 'Bea', 'lastname' => 'Cookiespec', 'email' => 'bea.cookiespec@example.test', 'status' => 'Superuser', 'password' => 'stored-hash']);
    $this->good = $this->boss->user_id . '.' . md5('stored-hash');
});

afterEach(function () {
    $_COOKIE = $this->saved;
});

describe('THEUSER from the cookie', function () {
    describe('with the right password', function () {
        it('signs in', function () {
            expect(the_user_spec_for($this->good)->isloggedin())->toBeTrue();
        });

        it('can open the admin pages', function () {
            expect(the_user_spec_for($this->good)->is_able_to('viewadminsection'))->toBeTrue();
        });
    });

    describe('with a wrong password', function () {
        dataset('forged', [
            'junk' => fn () => $this->boss->user_id . '.0000000000000000wrong',
            'a zero, for loose comparison' => fn () => $this->boss->user_id . '.0',
            'nothing after the dot' => fn () => $this->boss->user_id . '.',
        ]);

        it('does not sign in', function (string $cookie) {
            expect(the_user_spec_for($cookie)->isloggedin())->toBeFalse();
        })->with('forged');

        it('cannot open the admin pages', function (string $cookie) {
            expect(the_user_spec_for($cookie)->is_able_to('viewadminsection'))->toBeFalse();
        })->with('forged');

        it('keeps no role', function (string $cookie) {
            expect(the_user_spec_for($cookie)->status())->toBe('Viewer');
        })->with('forged');

        it('keeps no password hash', function (string $cookie) {
            expect(the_user_spec_for($cookie)->password())->toBe('');
        })->with('forged');

        it('keeps no user id', function (string $cookie) {
            expect(the_user_spec_for($cookie)->user_id())->toBe('0');
        })->with('forged');
    });

    describe('for a deleted user', function () {
        it('keeps no role', function () {
            $this->boss->update(['deleted' => true]);

            expect(the_user_spec_for($this->good)->status())->toBe('Viewer');
        });
    });

    describe('for no such user', function () {
        it('cannot open the admin pages', function () {
            expect(the_user_spec_for('999999999.' . md5('stored-hash'))->is_able_to('viewadminsection'))->toBeFalse();
        });
    });
});
