<?php

/**
 * @file
 * Changing a password signs the user out everywhere. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * A fictional user, with a "remember me" token.
 */
function sign_out_spec_user(string $name, string $status = 'User'): User {
    $user = User::create([
        'firstname' => $name,
        'lastname' => 'Signoutspec',
        'email' => strtolower($name) . '.signoutspec@example.test',
        'password' => password_hash('old-password', PASSWORD_DEFAULT),
        'status' => $status,
        'confirmed' => true,
    ]);
    $user->forceFill(['remember_token' => 'old-remember-token'])->save();

    return $user;
}

/**
 * A session row for a user. A session id is 40 letters and digits.
 */
function sign_out_spec_session(string $id, ?User $user): void {
    Session::create(['id' => str_pad($id, 40, '0'), 'user_id' => $user?->user_id, 'payload' => '', 'last_activity' => time()]);
}

/**
 * The details form that update_self() and update_other_user() take, with a new password.
 *
 * @return array<string, mixed>
 *   The details.
 */
function sign_out_spec_details(User $user, string $password): array {
    return [
        'user_id' => $user->user_id,
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'email' => $user->email,
        'emailpublic' => false,
        'constituency' => '',
        'url' => '',
        'optin' => false,
        'password' => $password,
    ];
}

beforeEach(function () {
    $this->ann = sign_out_spec_user('Ann');
    $this->bob = sign_out_spec_user('Bob');
    sign_out_spec_session('annone', $this->ann);
    sign_out_spec_session('anntwo', $this->ann);
    sign_out_spec_session('bobone', $this->bob);
    $this->savedUser = $GLOBALS['THEUSER'] ?? null;
});

afterEach(function () {
    $GLOBALS['THEUSER'] = $this->savedUser;
    twfy_laravel()->make('auth')->forgetGuards();
    twfy_laravel()->make('session')->forgetDrivers();
    twfy_laravel()->forgetInstance('session.store');
});

describe('User::signOutEverywhere()', function () {
    it('deletes the user\'s sessions', function () {
        $this->ann->signOutEverywhere();

        expect(Session::where('user_id', $this->ann->user_id)->count())->toBe(0);
    });

    it('leaves other users\' sessions', function () {
        $this->ann->signOutEverywhere();

        expect(Session::where('user_id', $this->bob->user_id)->count())->toBe(1);
    });

    it('replaces the "remember me" token', function () {
        $this->ann->signOutEverywhere();

        expect($this->ann->fresh()->remember_token)->not->toBe('old-remember-token');
    });

    it('leaves other users\' "remember me" tokens', function () {
        $this->ann->signOutEverywhere();

        expect($this->bob->fresh()->remember_token)->toBe('old-remember-token');
    });

    describe('keeping one session', function () {
        it('leaves that one', function () {
            $this->ann->signOutEverywhere(str_pad('annone', 40, '0'));

            expect(Session::where('user_id', $this->ann->user_id)->pluck('id')->all())->toBe([str_pad('annone', 40, '0')]);
        });
    });
});

describe('changing a password', function () {
    describe('with the forgotten password page', function () {
        it('signs the user out everywhere', function () {
            (new \USER())->change_password($this->ann->email);

            expect(Session::where('user_id', $this->ann->user_id)->count())->toBe(0);
        });
    });

    describe('on the account page', function () {
        beforeEach(function () {
            twfy_laravel()->make('session.store')->setId(str_pad('annone', 40, '0'));
            twfy_laravel()->make('auth')->guard()->setUser($this->ann);
            $GLOBALS['THEUSER'] = new THEUSER();
            $GLOBALS['THEUSER']->update_self(sign_out_spec_details($this->ann, 'new-password'));
        });

        it('keeps the session it was changed in', function () {
            expect(Session::where('user_id', $this->ann->user_id)->pluck('id')->all())->toBe([str_pad('annone', 40, '0')]);
        });

        it('replaces the "remember me" token', function () {
            expect($this->ann->fresh()->remember_token)->not->toBe('old-remember-token');
        });

        it('saves the password', function () {
            expect(password_verify('new-password', $this->ann->fresh()->password))->toBeTrue();
        });
    });

    describe('by an administrator', function () {
        it('signs the other user out everywhere', function () {
            $admin = sign_out_spec_user('Cat', 'Superuser');
            twfy_laravel()->make('auth')->guard()->setUser($admin);
            $GLOBALS['THEUSER'] = new THEUSER();

            $GLOBALS['THEUSER']->update_other_user(sign_out_spec_details($this->ann, 'new-password'));

            expect(Session::where('user_id', $this->ann->user_id)->count())->toBe(0);
        });
    });

    describe('without a new password', function () {
        it('leaves the sessions', function () {
            twfy_laravel()->make('auth')->guard()->setUser($this->ann);
            $GLOBALS['THEUSER'] = new THEUSER();

            $GLOBALS['THEUSER']->update_self(sign_out_spec_details($this->ann, ''));

            expect(Session::where('user_id', $this->ann->user_id)->count())->toBe(2);
        });
    });
});
