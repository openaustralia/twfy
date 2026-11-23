<?php

/**
 * @file
 * THEUSER signing in from Laravel's session. Uses a fictional Superuser.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * The THEUSER for a visitor Laravel has signed in as a user, or as nobody.
 */
function the_user_spec_for(?User $user): THEUSER {
    $guard = twfy_laravel()->make('auth')->guard();
    $user ? $guard->setUser($user) : $guard->forgetUser();
    return new THEUSER();
}

beforeEach(function () {
    $this->boss = User::create(['firstname' => 'Bea', 'lastname' => 'Sessionspec', 'email' => 'bea.sessionspec@example.test', 'status' => 'Superuser', 'password' => 'stored-hash']);
});

afterEach(function () {
    laravel_spec_new_request();
});

describe('THEUSER from the session', function () {
    describe('for a signed-in user', function () {
        it('signs in', function () {
            expect(the_user_spec_for($this->boss)->isloggedin())->toBeTrue();
        });

        it('has their id', function () {
            expect(the_user_spec_for($this->boss)->user_id())->toBe($this->boss->user_id);
        });

        it('can open the admin pages', function () {
            expect(the_user_spec_for($this->boss)->is_able_to('viewadminsection'))->toBeTrue();
        });
    });

    describe('for a deleted user', function () {
        beforeEach(fn () => $this->boss->update(['deleted' => true]));

        it('does not sign in', function () {
            expect(the_user_spec_for($this->boss)->isloggedin())->toBeFalse();
        });

        it('keeps no role', function () {
            expect(the_user_spec_for($this->boss)->status())->toBe('Viewer');
        });

        it('keeps no password hash', function () {
            expect(the_user_spec_for($this->boss)->password())->toBe('');
        });
    });

    describe('for nobody', function () {
        it('does not sign in', function () {
            expect(the_user_spec_for(null)->isloggedin())->toBeFalse();
        });

        it('cannot open the admin pages', function () {
            expect(the_user_spec_for(null)->is_able_to('viewadminsection'))->toBeFalse();
        });

        it('keeps no role', function () {
            expect(the_user_spec_for(null)->status())->toBe('Viewer');
        });
    });

    describe('with the old epuser_id cookie', function () {
        it('does not sign in', function () {
            $_COOKIE = ['epuser_id' => $this->boss->user_id . '.' . md5('stored-hash')];

            try {
                expect(the_user_spec_for(null)->isloggedin())->toBeFalse();
            } finally {
                $_COOKIE = [];
            }
        });
    });
});
