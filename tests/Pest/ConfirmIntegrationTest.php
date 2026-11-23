<?php

/**
 * @file
 * Following the confirmation link from the join email, where it stops early. UserIntegrationTest covers a good link.
 * Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * A THEUSER that counts sign-ins, so the spec doesn't start a real session.
 */
function confirm_spec_the_user(): THEUSER {
    return new class extends THEUSER {

        public int $signIns = 0;

        public function login(string $returl = ''): void {
            $this->signIns++;
        }

    };
}

beforeEach(function () {
    $this->user = User::create([
        'firstname' => 'Fay',
        'lastname' => 'Confirmspec',
        'email' => 'fay.confirmspec@example.test',
        'password' => password_hash('a-password', PASSWORD_DEFAULT),
        'registrationtoken' => 'confirmspectoken1',
        'confirmed' => false,
        'deleted' => false,
    ]);
    $this->link = $this->user->user_id . '-confirmspectoken1';
});

describe('THEUSER::confirm()', function () {
    describe('for a deleted user', function () {
        beforeEach(fn () => $this->user->update(['deleted' => true]));

        it('answers false', function () {
            expect(confirm_spec_the_user()->confirm($this->link))->toBeFalse();
        });

        it('confirms nothing', function () {
            confirm_spec_the_user()->confirm($this->link);

            expect($this->user->fresh()->confirmed)->toBeFalse();
        });

        it('signs nobody in', function () {
            $theUser = confirm_spec_the_user();
            $theUser->confirm($this->link);

            expect($theUser->signIns)->toBe(0);
        });
    });

    describe('with the wrong token', function () {
        it('signs nobody in', function () {
            $theUser = confirm_spec_the_user();
            $theUser->confirm($this->user->user_id . '-wrongtoken');

            expect($theUser->signIns)->toBe(0);
        });
    });
});
