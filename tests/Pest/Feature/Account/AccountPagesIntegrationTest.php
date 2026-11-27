<?php

/**
 * @file
 * Someone's own details at /account and /account/edit. Uses fictional users.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AccountRequests.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

defined('WEBPATH') || define('WEBPATH', '/');

beforeEach(function () {
    MemberPageStubs::bind();

    $this->user = User::create([
        'firstname' => 'Pat',
        'lastname' => 'Pagespec',
        'email' => 'pat.pagespec@example.test',
        'password' => 'stored-hash',
        'status' => 'User',
        'optin' => true,
        'registrationtime' => '2025-03-04 05:06:07',
        'confirmed' => true,
    ]);
    $this->session = AccountRequests::signIn($this->user);
    $this->get = fn (string $uri) => AccountRequests::send('GET', $uri, [], $this->session);
});

afterEach(function () {
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('GET /account', function () {
    describe('for someone signed out', function () {
        it('sends them to sign in', function () {
            expect(AccountRequests::send('GET', '/account')->getStatusCode())->toBe(302);
        });

        it('brings them back afterwards', function () {
            expect(AccountRequests::send('GET', '/account')->headers->get('Location'))->toEndWith('/user/login?ret=%2Faccount');
        });
    });

    describe('for someone signed in', function () {
        it('draws the page in the site layout', function () {
            expect(($this->get)('/account')->getContent())->toContain('[top:userviewself:]', '[bottom]');
        });

        it('shows their name', function () {
            expect(($this->get)('/account')->getContent())->toContain('Pat Pagespec');
        });

        it('shows their email address', function () {
            expect(($this->get)('/account')->getContent())->toContain('pat.pagespec@example.test');
        });

        it('shows their account type', function () {
            expect(($this->get)('/account')->getContent())->toContain('>User<');
        });

        it('shows when they joined', function () {
            expect(($this->get)('/account')->getContent())->toContain('4 March 2025');
        });

        it('links to the edit page', function () {
            expect(($this->get)('/account')->getContent())->toContain('href="/account/edit"');
        });

        it('escapes their name', function () {
            $this->user->update(['firstname' => '<b>Pat</b>']);

            expect(($this->get)('/account')->getContent())->toContain('&lt;b&gt;Pat&lt;/b&gt;')->not->toContain('<b>Pat</b>');
        });

        it('mentions no change when none waits', function () {
            expect(($this->get)('/account')->getContent())->not->toContain('Waiting for you to confirm');
        });

        it('says when a new address is waiting', function () {
            $this->user->update(['pending_email' => 'new.pagespec@example.test']);

            expect(($this->get)('/account')->getContent())->toContain('Waiting for you to confirm your new email address')->toContain('new.pagespec@example.test');
        });

        it('says nothing is public', function () {
            expect(($this->get)('/account')->getContent())->not->toContain('how other people see you');
        });
    });
});

describe('GET /account/edit', function () {
    describe('for someone signed out', function () {
        it('sends them to sign in', function () {
            expect(AccountRequests::send('GET', '/account/edit')->headers->get('Location'))->toEndWith('/user/login?ret=%2Faccount%2Fedit');
        });
    });

    describe('for someone signed in', function () {
        it('draws the page in the site layout', function () {
            expect(($this->get)('/account/edit')->getContent())->toContain('[top:useredit:]');
        });

        it('fills in their first name', function () {
            expect(($this->get)('/account/edit')->getContent())->toContain('name="first_name" type="text" value="Pat"');
        });

        it('fills in their email address', function () {
            expect(($this->get)('/account/edit')->getContent())->toContain('value="pat.pagespec@example.test"');
        });

        it('has a CSRF token', function () {
            expect(($this->get)('/account/edit')->getContent())->toContain('name="_token" value="' . $this->session['token'] . '"');
        });

        it('leaves the password boxes empty', function () {
            expect(($this->get)('/account/edit')->getContent())->toContain('name="password" type="password" value=""');
        });

        it('does not ask for a current password', function () {
            expect(($this->get)('/account/edit')->getContent())->not->toContain('current_password');
        });

        it('chooses their news setting', function () {
            expect(($this->get)('/account/edit')->getContent())->toMatch('/value="1" checked/');
        });

        it('offers no public email or website', function () {
            expect(($this->get)('/account/edit')->getContent())->not->toContain('name="emailpublic"')->not->toContain('name="url"');
        });

        it('escapes what they typed into the name', function () {
            $this->user->update(['firstname' => '"><script>alert(1)</script>']);

            expect(($this->get)('/account/edit')->getContent())->not->toContain('<script>alert(1)</script>');
        });

        it('offers to resend or cancel', function () {
            $this->user->update(['pending_email' => 'new.pagespec@example.test']);

            expect(($this->get)('/account/edit')->getContent())->toContain('Send the link again')->toContain('Cancel this change');
        });
    });
});
