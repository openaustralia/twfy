<?php

/**
 * @file
 * Saving the form at /account/edit. Uses fictional users.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/AccountRequests.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use Illuminate\Support\Facades\RateLimiter;
use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

defined('WEBPATH') || define('WEBPATH', '/');
defined('CONTACTEMAIL') || define('CONTACTEMAIL', 'contact@example.test');
defined('DOMAIN') || define('DOMAIN', 'example.test');

beforeEach(function () {
    MemberPageStubs::bind();

    $this->sent = [];
    $GLOBALS['mail_transport'] = function (string $to, string $subject, string $message) {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'message' => $message];
        return true;
    };

    $this->user = User::create([
        'firstname' => 'Pat',
        'lastname' => 'Updatespec',
        'email' => 'pat.updatespec@example.test',
        'password' => password_hash('old-password', PASSWORD_DEFAULT),
        'optin' => true,
        'confirmed' => true,
    ]);
    $this->session = AccountRequests::signIn($this->user);
    $this->form = fn (array $changes = []) => $changes + [
        'first_name' => 'Pat',
        'last_name' => 'Updatespec',
        'email' => 'pat.updatespec@example.test',
        'password' => '',
        'password_confirmation' => '',
        'news' => '1',
    ];
    $this->save = fn (array $changes = []) => AccountRequests::send('PUT', '/account', ($this->form)($changes), $this->session);
    // The page the browser lands on next, which shows what the save flashed.
    $this->after = fn () => AccountRequests::send('GET', '/account/edit', [], $this->session)->getContent();
});

afterEach(function () {
    RateLimiter::clear('email-change:' . $this->user->getKey());
    unset($GLOBALS['mail_transport']);
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('PUT /account', function () {
    describe('with good details', function () {
        it('goes back to the form', function () {
            expect(($this->save)()->headers->get('Location'))->toEndWith('/account/edit');
        });

        it('says it saved', function () {
            ($this->save)();

            expect(($this->after)())->toContain('Your details have been saved.');
        });

        it('saves the names', function () {
            ($this->save)(['first_name' => 'Patricia', 'last_name' => 'Newname']);

            expect($this->user->fresh()->only('firstname', 'lastname'))->toBe(['firstname' => 'Patricia', 'lastname' => 'Newname']);
        });

        it('allows no last name', function () {
            ($this->save)(['last_name' => '']);

            expect($this->user->fresh()->only('firstname', 'lastname'))->toBe(['firstname' => 'Pat', 'lastname' => '']);
        });

        it('allows no first name', function () {
            ($this->save)(['first_name' => '']);

            expect($this->user->fresh()->only('firstname', 'lastname'))->toBe(['firstname' => '', 'lastname' => 'Updatespec']);
        });

        it('saves not wanting news', function () {
            ($this->save)(['news' => '0']);

            expect($this->user->fresh()->optin)->toBeFalse();
        });

        it('sends no email', function () {
            ($this->save)();

            expect($this->sent)->toBe([]);
        });

        it('keeps the password', function () {
            ($this->save)();

            expect(password_verify('old-password', $this->user->fresh()->password))->toBeTrue();
        });
    });

    describe('with a new email address', function () {
        beforeEach(fn () => ($this->save)(['email' => 'new.updatespec@example.test']));

        it('keeps the current address', function () {
            expect($this->user->fresh()->email)->toBe('pat.updatespec@example.test');
        });

        it('waits for the new address', function () {
            expect($this->user->fresh()->pending_email)->toBe('new.updatespec@example.test');
        });

        it('emails the new address a link', function () {
            expect($this->sent[0]['to'])->toBe('new.updatespec@example.test');
        });

        it('tells the current address', function () {
            expect($this->sent[1]['to'])->toBe('pat.updatespec@example.test');
        });

        it('tells them what to do next', function () {
            expect(($this->after)())->toContain('follow the link we&#039;ve emailed to new.updatespec@example.test');
        });

        it('shows the form with the current address', function () {
            expect(($this->after)())->toContain('value="pat.updatespec@example.test"');
        });
    });

    describe('after asking for too many emails', function () {
        beforeEach(function () {
            foreach (['a', 'b', 'c'] as $letter) {
                ($this->save)(['email' => "$letter.updatespec@example.test"]);
            }
            $this->sent = [];
            ($this->save)(['first_name' => 'Patricia', 'email' => 'd.updatespec@example.test']);
        });

        it('still saves the other details', function () {
            expect($this->user->fresh()->firstname)->toBe('Patricia');
        });

        it('keeps the address that was waiting', function () {
            expect($this->user->fresh()->pending_email)->toBe('c.updatespec@example.test');
        });

        it('emails nobody', function () {
            expect($this->sent)->toBe([]);
        });

        it('says it has not changed the address', function () {
            expect(($this->after)())->toContain("We haven&#039;t changed your email address");
        });
    });

    describe('with the current address in other letters', function () {
        it('changes nothing', function () {
            ($this->save)(['email' => 'PAT.updatespec@example.test']);

            expect($this->user->fresh()->pending_email)->toBeNull();
        });
    });

    describe('with the address already waiting', function () {
        it('does not email it again', function () {
            ($this->save)(['email' => 'new.updatespec@example.test']);
            $this->sent = [];

            ($this->save)(['email' => 'new.updatespec@example.test']);

            expect($this->sent)->toBe([]);
        });
    });

    describe('with a password of the longest allowed length', function () {
        it('saves it', function () {
            $longest = str_repeat('a', 72);
            ($this->save)(['password' => $longest, 'password_confirmation' => $longest]);

            expect(password_verify($longest, $this->user->fresh()->password))->toBeTrue();
        });
    });

    describe('with a new password', function () {
        beforeEach(function () {
            $this->other = AccountRequests::signIn($this->user);
            ($this->save)(['password' => 'new-password', 'password_confirmation' => 'new-password']);
        });

        it('saves it as bcrypt', function () {
            expect($this->user->fresh()->password)->toStartWith('$2y$');
        });

        it('saves the new password', function () {
            expect(password_verify('new-password', $this->user->fresh()->password))->toBeTrue();
        });

        it('signs out other browsers', function () {
            expect(Session::find($this->other['id']))->toBeNull();
        });

        it('keeps this browser signed in', function () {
            expect(Session::find($this->session['id']))->not->toBeNull();
        });

        it('tells the current address', function () {
            expect($this->sent[0]['to'])->toBe('pat.updatespec@example.test');
        });

        it('sends no password', function () {
            expect($this->sent[0]['message'])->not->toContain('new-password');
        });
    });

    describe('with mistakes', function () {
        it('keeps the old details', function (array $changes) {
            ($this->save)($changes);

            expect($this->user->fresh()->only('firstname', 'email'))->toBe(['firstname' => 'Pat', 'email' => 'pat.updatespec@example.test']);
        })->with([
            'no name at all' => [['first_name' => '', 'last_name' => '', 'email' => 'new.updatespec@example.test']],
            'no email address' => [['first_name' => 'Patricia', 'email' => '']],
            'a bad email address' => [['first_name' => 'Patricia', 'email' => 'not-an-address']],
            'a short password' => [['first_name' => 'Patricia', 'password' => 'short', 'password_confirmation' => 'short']],
            'a password one character short' => [['first_name' => 'Patricia', 'password' => 'seven77', 'password_confirmation' => 'seven77']],
            'a password one character too long' => [['first_name' => 'Patricia', 'password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)]],
            'passwords that differ' => [['first_name' => 'Patricia', 'password' => 'new-password', 'password_confirmation' => 'other-password']],
            'no news choice' => [['first_name' => 'Patricia', 'news' => '']],
        ]);

        it('says what is wrong', function (array $changes, string $message) {
            ($this->save)($changes);

            expect(($this->after)())->toContain($message);
        })->with([
            'no name at all' => [['first_name' => '', 'last_name' => ''], 'Enter a first name or a last name'],
            'no email address' => [['email' => ''], 'Enter your email address'],
            'a bad email address' => [['email' => 'not-an-address'], 'Enter an email address like name@example.com'],
            'a short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'Your password needs at least 8 characters'],
            'a long password' => [['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)], 'Your password can have at most 72 characters'],
            'passwords that differ' => [['password' => 'new-password', 'password_confirmation' => 'other'], "The two passwords don&#039;t match"],
        ]);

        it('sends no email', function () {
            ($this->save)(['first_name' => '', 'last_name' => '', 'email' => 'new.updatespec@example.test']);

            expect($this->sent)->toBe([]);
        });

        it('keeps what they typed', function () {
            ($this->save)(['first_name' => '', 'last_name' => '', 'email' => 'typed.updatespec@example.test']);

            expect(($this->after)())->toContain('value="typed.updatespec@example.test"');
        });

        it('never fills a password box again', function () {
            ($this->save)(['first_name' => '', 'last_name' => '', 'password' => 'typed-secret', 'password_confirmation' => 'typed-secret']);

            expect(($this->after)())->not->toContain('typed-secret');
        });
    });

    describe('with an address another user has', function () {
        beforeEach(fn () => User::create(['firstname' => 'Quinn', 'lastname' => 'Updatespec', 'email' => 'quinn.updatespec@example.test', 'password' => 'stored-hash']));

        it('says so', function () {
            ($this->save)(['email' => 'quinn.updatespec@example.test']);

            expect(($this->after)())->toContain('Someone else has already joined with this email address');
        });

        it('says so in other letters too', function () {
            ($this->save)(['email' => 'QUINN.updatespec@example.test']);

            expect($this->user->fresh()->pending_email)->toBeNull();
        });

        it('emails nobody', function () {
            ($this->save)(['email' => 'quinn.updatespec@example.test']);

            expect($this->sent)->toBe([]);
        });
    });

    describe('without a CSRF token', function () {
        it('answers 419', function () {
            expect(AccountRequests::send('PUT', '/account', ($this->form)(['first_name' => 'Changed']), $this->session, false)->getStatusCode())->toBe(419);
        });

        it('changes nothing', function () {
            AccountRequests::send('PUT', '/account', ($this->form)(['first_name' => 'Changed']), $this->session, false);

            expect($this->user->fresh()->firstname)->toBe('Pat');
        });
    });

    describe('with the wrong CSRF token', function () {
        it('answers 419', function () {
            expect(AccountRequests::send('PUT', '/account', ($this->form)(['first_name' => 'Changed', '_token' => 'wrong']), $this->session, false)->getStatusCode())->toBe(419);
        });
    });

    describe('as a GET', function () {
        it('changes nothing', function () {
            AccountRequests::send('GET', '/account?' . http_build_query(($this->form)(['first_name' => 'Changed'])), [], $this->session);

            expect($this->user->fresh()->firstname)->toBe('Pat');
        });
    });

    describe('with an attempt to set other columns', function () {
        it('ignores them', function () {
            ($this->save)(['status' => 'Superuser', 'confirmed' => '0', 'deleted' => '1', 'user_id' => '1']);

            expect($this->user->fresh()->only('status', 'confirmed', 'deleted'))->toBe(['status' => 'Viewer', 'confirmed' => true, 'deleted' => false]);
        });
    });
});
