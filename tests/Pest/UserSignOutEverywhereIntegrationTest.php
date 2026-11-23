<?php

/**
 * @file
 * Ending every sign-in when a password changes. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Adds a session row for a user.
 */
function sign_out_spec_session(string $id, int $user_id): void {
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $user_id, 'payload' => '', 'last_activity' => time()]);
}

beforeEach(function () {
    $this->ada = User::create(['firstname' => 'Ada', 'lastname' => 'Signoutspec', 'email' => 'ada.signoutspec@example.test', 'password' => 'old-hash', 'remember_token' => 'old-token']);
    $this->bea = User::create(['firstname' => 'Bea', 'lastname' => 'Signoutspec', 'email' => 'bea.signoutspec@example.test', 'password' => 'bea-hash']);
    sign_out_spec_session('ada-laptop', (int) $this->ada->user_id);
    sign_out_spec_session('bea-laptop', (int) $this->bea->user_id);
});

describe('User', function () {
    describe('signOutEverywhere()', function () {
        beforeEach(fn () => $this->ada->signOutEverywhere());

        it('deletes their sessions', function () {
            expect(DB::table('sessions')->where('user_id', $this->ada->user_id)->count())->toBe(0);
        });

        it('leaves other people\'s sessions', function () {
            expect(DB::table('sessions')->where('user_id', $this->bea->user_id)->count())->toBe(1);
        });

        it('replaces the "remember me" token', function () {
            expect(User::find($this->ada->user_id)->remember_token)->not->toBe('old-token');
        });
    });
});

describe('USER', function () {
    describe('change_password()', function () {
        beforeEach(fn () => (new \USER())->change_password('ada.signoutspec@example.test'));

        it('deletes their sessions', function () {
            expect(DB::table('sessions')->where('user_id', $this->ada->user_id)->count())->toBe(0);
        });

        it('replaces the "remember me" token', function () {
            expect(User::find($this->ada->user_id)->remember_token)->not->toBe('old-token');
        });
    });
});
