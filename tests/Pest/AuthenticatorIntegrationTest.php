<?php

/**
 * @file
 * Finding the user for Fortify's login. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenAustralia\TWFY\Auth\Authenticator;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * A user who can sign in, unless the attributes say otherwise.
 *
 * @param array<string, mixed> $attributes
 *   Columns to set.
 */
function authenticator_spec_user(array $attributes = []): User {
    return User::create($attributes + [
        'firstname' => 'Ada',
        'lastname' => 'Authspec',
        'email' => 'ada.authspec@example.test',
        'password' => password_hash('correct-horse', PASSWORD_DEFAULT),
        'confirmed' => true,
        'deleted' => false,
    ]);
}

describe('Authenticator', function () {
    describe('authenticate()', function () {
        describe('with a bcrypt password', function () {
            beforeEach(fn () => authenticator_spec_user());

            it('finds the user when it matches', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'correct-horse')?->email)->toBe('ada.authspec@example.test');
            });

            it('finds nobody when it differs', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'wrong'))->toBeNull();
            });

            it('finds nobody for another email address', function () {
                expect((new Authenticator())->authenticate('nobody.authspec@example.test', 'correct-horse'))->toBeNull();
            });

            it('finds nobody for an empty password', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', ''))->toBeNull();
            });
        });

        describe('with an md5-crypt password', function () {
            beforeEach(function () {
                $this->user = authenticator_spec_user(['password' => crypt('legacy-pass', '$1$testsalt$')]);
            });

            it('finds the user when it matches', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'legacy-pass')?->email)->toBe('ada.authspec@example.test');
            });

            it('upgrades it to bcrypt', function () {
                (new Authenticator())->authenticate('ada.authspec@example.test', 'legacy-pass');

                expect(password_verify('legacy-pass', $this->user->fresh()->password))->toBeTrue();
            });

            it('finds nobody when it differs', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'wrong'))->toBeNull();
            });

            it('leaves the hash alone when it differs', function () {
                (new Authenticator())->authenticate('ada.authspec@example.test', 'wrong');

                expect($this->user->fresh()->password)->toStartWith('$1$');
            });
        });

        describe('for a deleted user', function () {
            it('finds nobody', function () {
                authenticator_spec_user(['deleted' => true]);

                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'correct-horse'))->toBeNull();
            });
        });

        describe('for an unconfirmed user', function () {
            beforeEach(fn () => authenticator_spec_user(['confirmed' => false]));

            it('says to confirm the email address', function () {
                expect(fn () => (new Authenticator())->authenticate('ada.authspec@example.test', 'correct-horse'))
                    ->toThrow(ValidationException::class, "You haven't confirmed your email address yet.");
            });

            it('finds nobody when the password differs', function () {
                expect((new Authenticator())->authenticate('ada.authspec@example.test', 'wrong'))->toBeNull();
            });
        });
    });

    describe('__invoke()', function () {
        it('reads the login form fields', function () {
            authenticator_spec_user();
            $request = Request::create('/user/login', 'POST', ['email' => 'ada.authspec@example.test', 'password' => 'correct-horse']);

            expect((new Authenticator())($request)?->email)->toBe('ada.authspec@example.test');
        });
    });
});
