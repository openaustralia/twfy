<?php

/**
 * @file
 * Page-level specs for /user/ when a signed-in user changes their details.
 * Uses a fictional person. The page runs in another process against the
 * committed database, so the row is real and removed afterwards.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\UserRoles;

uses(PageRenderingIntegrationTestCase::class);

const USER_EDIT_PAGE = __DIR__ . '/../../www/docs/user/index.php';
const USER_EDIT_PAGE_LASTNAME = 'Usereditspec';

afterEach(function () {
    User::where('lastname', USER_EDIT_PAGE_LASTNAME)->delete();
});

describe('/user/ submitted when signed in', function () {
    beforeEach(function () {
        $this->hash = password_hash('Old-passw0rd', PASSWORD_DEFAULT);
        $this->bea = User::create([
            'firstname' => 'Bea',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'email' => 'bea.usereditspec@example.test',
            'status' => 'User',
            'password' => $this->hash,
        ]);
        $this->cookies = ['epuser_id' => $this->bea->user_id . '.' . md5($this->hash)];
        $this->token = UserRoles::token($this->bea->user_id, $this->hash, 'user-edit');
        $this->form = [
            'pg' => 'edit',
            'submitted' => 'true',
            'firstname' => 'Bea',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'em' => 'new.usereditspec@example.test',
            'password' => 'New-passw0rd',
            'password2' => 'New-passw0rd',
            'url' => '',
            'constituency' => '',
        ];
        $this->stored = fn (): User => User::findOrFail($this->bea->user_id);
    });

    // What a link on another site, or an image tag, makes the browser send.
    describe('with a GET', function () {
        it('answers 403', function (string $pg) {
            $form = ['pg' => $pg] + $this->form;

            expect($this->renderPage(USER_EDIT_PAGE, 'GET', $form, [], $this->cookies)['status'])->toBe(403);
        })->with(['edit', 'editother', '']);

        it('leaves the email alone', function (string $pg) {
            $form = ['pg' => $pg] + $this->form;
            $this->renderPage(USER_EDIT_PAGE, 'GET', $form, [], $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        })->with(['edit', 'editother', '']);

        it('leaves the password alone', function () {
            $this->renderPage(USER_EDIT_PAGE, 'GET', $this->form, [], $this->cookies);

            expect(($this->stored)()->password)->toBe($this->hash);
        });

        it('ignores a token in the URL', function () {
            $this->renderPage(USER_EDIT_PAGE, 'GET', ['token' => $this->token] + $this->form, [], $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        });
    });

    describe('with a POST and no token', function () {
        it('answers 403', function () {
            expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $this->form, $this->cookies)['status'])->toBe(403);
        });

        it('leaves the email alone', function () {
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], $this->form, $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        });
    });

    describe('with a POST and a wrong token', function () {
        it('leaves the email alone', function () {
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], ['token' => 'forged'] + $this->form, $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        });
    });

    describe('with a POST, token not a string', function () {
        it('runs without error', function () {
            expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], ['token' => ['forged']] + $this->form, $this->cookies)['exit'])->toBe(0);
        });
    });

    describe('with a POST, the roles form token', function () {
        it('leaves the email alone', function () {
            $token = UserRoles::token($this->bea->user_id, $this->hash);
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], ['token' => $token] + $this->form, $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        });
    });

    describe('with a POST and their token', function () {
        it('changes the email', function () {
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], ['token' => $this->token] + $this->form, $this->cookies);

            expect(($this->stored)()->email)->toBe('new.usereditspec@example.test');
        });
    });
});
