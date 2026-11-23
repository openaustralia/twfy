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

describe('/user/ edit forms', function () {
    beforeEach(function () {
        $this->hash = password_hash('Old-passw0rd', PASSWORD_DEFAULT);
        $this->boss = User::create([
            'firstname' => 'Bea',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'email' => 'bea.usereditspec@example.test',
            'status' => 'Superuser',
            'password' => $this->hash,
        ]);
        $this->max = User::create([
            'firstname' => "Max O'Neil",
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'email' => 'max.usereditspec@example.test',
            'status' => 'User',
            'password' => 'hash-of-a-password',
        ]);
        $this->ari = User::create([
            'firstname' => 'Ari',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'email' => 'ari.usereditspec@example.test',
            'status' => 'User',
            'password' => 'another-hash',
        ]);
        $this->cookies = ['epuser_id' => $this->boss->user_id . '.' . md5($this->hash)];
        $this->tokenFor = fn (?int $target): string => UserRoles::token(
            $this->boss->user_id,
            $this->hash,
            $target === null ? 'user-edit' : 'user-edit:' . $target
        );
        $this->form = fn (int $target, string $token): array => [
            'pg' => 'editother',
            'u' => (string) $target,
            'submitted' => 'true',
            'token' => $token,
            'firstname' => 'Max',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'em' => 'max.changed.usereditspec@example.test',
            'status' => 'User',
            'url' => '',
            'constituency' => '',
        ];
        $this->maxEmail = fn (): string => User::findOrFail($this->max->user_id)->email;
    });

    describe('the self-edit form', function () {
        it('contains the token', function () {
            $page = $this->renderPage(USER_EDIT_PAGE, 'GET', ['pg' => 'edit'], [], $this->cookies);

            expect($page['output'])->toContain('name="token" value="' . ($this->tokenFor)(null) . '"');
        });
    });

    describe('the other-user edit form as a Superuser', function () {
        it('contains the matching token', function () {
            $page = $this->renderPage(USER_EDIT_PAGE, 'GET', ['pg' => 'editother', 'u' => (string) $this->max->user_id], [], $this->cookies);

            expect($page['output'])->toContain('name="token" value="' . ($this->tokenFor)($this->max->user_id) . '"');
        });

        it('does not carry the self-edit token', function () {
            $page = $this->renderPage(USER_EDIT_PAGE, 'GET', ['pg' => 'editother', 'u' => (string) $this->max->user_id], [], $this->cookies);

            expect($page['output'])->not->toContain(($this->tokenFor)(null));
        });
    });

    describe('a Superuser POST to edit another user', function () {
        describe('with the right token', function () {
            it('does not answer 403', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));

                expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies)['status'])->not->toBe(403);
            });

            it('changes the email', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));
                $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);

                expect(($this->maxEmail)())->toBe('max.changed.usereditspec@example.test');
            });
        });

        describe('with a token for a different target', function () {
            it('answers 403', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->ari->user_id));

                expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies)['status'])->toBe(403);
            });

            it('changes nothing', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->ari->user_id));
                $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);

                expect(($this->maxEmail)())->toBe('max.usereditspec@example.test');
            });

            it('links back to the edited user', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->ari->user_id));
                $page = $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);

                expect($page['output'])->toContain('u=' . $this->max->user_id);
            });
        });

        describe('with the self-edit token', function () {
            it('answers 403', function () {
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)(null));

                expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies)['status'])->toBe(403);
            });
        });

        describe('with an id that is not a whole number', function () {
            it('answers 403', function () {
                $post = ['u' => '90210.5'] + ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));

                expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies)['status'])->toBe(403);
            });

            it('links to no id from the request', function () {
                $post = ['u' => '90210.5'] + ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));
                $page = $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);

                expect($page['output'])->not->toContain('u=90210');
            });
        });
    });

    describe('a join POST with no token', function () {
        it('is not refused', function () {
            $post = ['pg' => 'join', 'submitted' => 'true', 'firstname' => 'Cam', 'lastname' => USER_EDIT_PAGE_LASTNAME];

            expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post)['status'])->not->toBe(403);
        });

        it('creates no one', function () {
            $post = ['pg' => 'join', 'submitted' => 'true', 'firstname' => 'Cam', 'lastname' => USER_EDIT_PAGE_LASTNAME];
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post);

            expect(User::where('firstname', 'Cam')->where('lastname', USER_EDIT_PAGE_LASTNAME)->count())->toBe(0);
        });
    });
});
