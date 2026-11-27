<?php

/**
 * @file
 * Page-level specs for /user/, which joins people and lets an administrator edit another user. Someone's own
 * details are at /account/. Uses fictional people. The page runs in another process against the
 * committed database, so the row is real and removed afterwards.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\UserRoles;

uses(PageRenderingIntegrationTestCase::class);

const USER_EDIT_PAGE = __DIR__ . '/../../www/docs/user/index.php';
const USER_EDIT_PAGE_LASTNAME = 'Usereditspec';

afterEach(function () {
    Alert::where('email', 'like', '%.usereditspec@example.test')->delete();
    User::where('lastname', USER_EDIT_PAGE_LASTNAME)->delete();
});

describe('/user/ for someone\'s own details', function () {
    beforeEach(function () {
        $this->hash = password_hash('Old-passw0rd', PASSWORD_DEFAULT);
        $this->bea = User::create([
            'firstname' => 'Bea',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'email' => 'bea.usereditspec@example.test',
            'status' => 'User',
            'password' => $this->hash,
        ]);
        $this->cookies = $this->signedInCookies($this->bea);
        $this->token = UserRoles::token($this->bea->user_id, $this->hash, 'user-edit');
        $this->form = [
            'submitted' => 'true',
            'firstname' => 'Bea',
            'lastname' => USER_EDIT_PAGE_LASTNAME,
            'em' => 'new.usereditspec@example.test',
            'password' => 'New-passw0rd',
            'password2' => 'New-passw0rd',
            'constituency' => '',
        ];
        $this->stored = fn (): User => User::findOrFail($this->bea->user_id);
    });

    describe('with a GET', function () {
        it('redirects to the account pages', function (string $pg) {
            expect($this->renderPage(USER_EDIT_PAGE, 'GET', ['pg' => $pg], [], $this->cookies)['status'])->toBe(301);
        })->with(['edit', 'editother', '']);

        it('shows no form', function (string $pg) {
            expect($this->renderPage(USER_EDIT_PAGE, 'GET', ['pg' => $pg], [], $this->cookies)['output'])->toBe('');
        })->with(['edit', 'editother', '']);
    });

    describe('with a POST of the old form, and their token', function () {
        it('redirects', function () {
            expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], ['pg' => 'edit', 'token' => $this->token] + $this->form, $this->cookies)['status'])->toBe(301);
        });

        it('leaves the email alone', function () {
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], ['pg' => 'edit', 'token' => $this->token] + $this->form, $this->cookies);

            expect(($this->stored)()->email)->toBe('bea.usereditspec@example.test');
        });

        it('leaves the password alone', function () {
            $this->renderPage(USER_EDIT_PAGE, 'POST', [], ['pg' => 'edit', 'token' => $this->token] + $this->form, $this->cookies);

            expect(($this->stored)()->password)->toBe($this->hash);
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
        $this->cookies = $this->signedInCookies($this->boss);
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
            'constituency' => '',
        ];
        $this->maxEmail = fn (): string => User::findOrFail($this->max->user_id)->email;
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

        describe('with an email address another user has', function () {
            it('keeps the old email', function () {
                $post = ['em' => 'ari.usereditspec@example.test'] + ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));
                $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);

                expect(($this->maxEmail)())->toBe('max.usereditspec@example.test');
            });

            it('says so', function () {
                $post = ['em' => 'ari.usereditspec@example.test'] + ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));

                expect($this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies)['output'])->toContain('Someone else has already joined with this email address');
            });
        });

        describe('with a new email address', function () {
            beforeEach(function () {
                $this->maxAlert = Alert::create(['email' => 'max.usereditspec@example.test', 'criteria' => 'usereditspec', 'registrationtoken' => 'x', 'confirmed' => true]);
                $this->bossAlert = Alert::create(['email' => 'bea.usereditspec@example.test', 'criteria' => 'usereditspec', 'registrationtoken' => 'x', 'confirmed' => true]);
                $this->max->update(['pending_email' => 'waiting.usereditspec@example.test']);
                $post = ($this->form)($this->max->user_id, ($this->tokenFor)($this->max->user_id));
                $this->renderPage(USER_EDIT_PAGE, 'POST', [], $post, $this->cookies);
            });

            it('cancels a change waiting for the old one', function () {
                expect($this->max->fresh()->pending_email)->toBeNull();
            });

            it('moves the edited user\'s alerts', function () {
                expect($this->maxAlert->fresh()->email)->toBe('max.changed.usereditspec@example.test');
            });

            it('leaves the admin\'s alerts alone', function () {
                expect($this->bossAlert->fresh()->email)->toBe('bea.usereditspec@example.test');
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
