<?php

/**
 * @file
 * The link in the email to a new address, and resending or cancelling the change. Uses fictional users.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/AccountRequests.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use Illuminate\Support\Facades\URL;
use OpenAustralia\TWFY\Account\EmailChange;
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

    $this->user = User::create(['firstname' => 'Pat', 'lastname' => 'Routespec', 'email' => 'pat.routespec@example.test', 'password' => 'stored-hash', 'confirmed' => true]);
    $this->session = AccountRequests::signIn($this->user);
    (new EmailChange())->request($this->user, 'new.routespec@example.test');
    $this->link = (new EmailChange())->confirmationUrl($this->user);
    $this->sent = [];
    $this->click = fn (?string $link = null, ?array $session = null) => AccountRequests::send('GET', $link ?? $this->link, [], $session === [] ? null : ($session ?? $this->session));
});

afterEach(function () {
    unset($GLOBALS['mail_transport']);
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('GET /account/email/confirm', function () {
    describe('signed in as the user the link is for', function () {
        it('changes the email address', function () {
            ($this->click)();

            expect($this->user->fresh()->email)->toBe('new.routespec@example.test');
        });

        it('goes to the details page', function () {
            expect(($this->click)()->headers->get('Location'))->toEndWith('/account');
        });

        it('says what changed', function () {
            ($this->click)();

            expect(AccountRequests::send('GET', '/account', [], $this->session)->getContent())->toContain('Your email address is now new.routespec@example.test.');
        });

        it('keeps them signed in', function () {
            ($this->click)();

            expect(AccountRequests::send('GET', '/account', [], $this->session)->getStatusCode())->toBe(200);
        });
    });

    describe('with the signature altered', function () {
        it('answers 403', function () {
            expect(($this->click)($this->link . '0')->getStatusCode())->toBe(403);
        });

        it('changes nothing', function () {
            ($this->click)($this->link . '0');

            expect($this->user->fresh()->email)->toBe('pat.routespec@example.test');
        });
    });

    describe('with the hash swapped for another address', function () {
        it('answers 403', function () {
            $hash = hash('sha256', 'attacker.routespec@example.test');
            $swapped = preg_replace('~[0-9a-f]{64}\?~', $hash . '?', $this->link);

            expect(($this->click)($swapped)->getStatusCode())->toBe(403);
        });
    });

    describe('after it has expired', function () {
        it('answers 403', function () {
            $expired = URL::temporarySignedRoute('account.email.confirm', now()->subMinute(), ['user' => $this->user->user_id, 'hash' => hash('sha256', 'new.routespec@example.test')]);

            expect(($this->click)($expired)->getStatusCode())->toBe(403);
        });
    });

    describe('signed in as someone else', function () {
        beforeEach(function () {
            $this->stranger = User::create(['firstname' => 'Sam', 'lastname' => 'Routespec', 'email' => 'sam.routespec@example.test', 'password' => 'stored-hash', 'confirmed' => true]);
            $this->strangerSession = AccountRequests::signIn($this->stranger);
        });

        it('answers 403', function () {
            expect(($this->click)(null, $this->strangerSession)->getStatusCode())->toBe(403);
        });

        it('changes nothing', function () {
            ($this->click)(null, $this->strangerSession);

            expect($this->user->fresh()->email)->toBe('pat.routespec@example.test');
        });
    });

    describe('signed out', function () {
        it('sends them to sign in', function () {
            expect(($this->click)(null, [])->headers->get('Location'))->toContain('/user/login?ret=');
        });

        it('brings them back to the link', function () {
            $location = ($this->click)(null, [])->headers->get('Location');
            parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

            expect($query['ret'])->toBe(parse_url($this->link, PHP_URL_PATH) . '?' . parse_url($this->link, PHP_URL_QUERY));
        });

        it('changes nothing', function () {
            ($this->click)(null, []);

            expect($this->user->fresh()->email)->toBe('pat.routespec@example.test');
        });
    });

    describe('after the change was cancelled', function () {
        it('says the link has stopped working', function () {
            (new EmailChange())->cancel($this->user);

            ($this->click)();

            expect(AccountRequests::send('GET', '/account', [], $this->session)->getContent())->toContain('That link has stopped working');
        });
    });

    describe('when asked too often', function () {
        it('answers 429', function () {
            $statuses = [];
            foreach (range(1, 7) as $_) {
                $statuses[] = ($this->click)($this->link . '0')->getStatusCode();
            }

            expect(end($statuses))->toBe(429);
        });
    });
});

describe('POST /account/email/resend', function () {
    it('emails the link to the waiting address', function () {
        AccountRequests::send('POST', '/account/email/resend', [], $this->session);

        expect(array_column($this->sent, 'to'))->toBe(['new.routespec@example.test']);
    });

    it('goes back to the form', function () {
        expect(AccountRequests::send('POST', '/account/email/resend', [], $this->session)->headers->get('Location'))->toEndWith('/account/edit');
    });

    it('needs a CSRF token', function () {
        expect(AccountRequests::send('POST', '/account/email/resend', [], $this->session, false)->getStatusCode())->toBe(419);
    });

    it('needs someone signed in', function () {
        expect(AccountRequests::send('POST', '/account/email/resend')->getStatusCode())->toBe(419);
    });

    it('answers 429 when asked too often', function () {
        $statuses = [];
        foreach (range(1, 4) as $_) {
            $statuses[] = AccountRequests::send('POST', '/account/email/resend', [], $this->session)->getStatusCode();
        }

        expect(end($statuses))->toBe(429);
    });
});

describe('DELETE /account/email', function () {
    it('cancels the change', function () {
        AccountRequests::send('DELETE', '/account/email', [], $this->session);

        expect($this->user->fresh()->pending_email)->toBeNull();
    });

    it('stops the link working', function () {
        AccountRequests::send('DELETE', '/account/email', [], $this->session);

        ($this->click)();

        expect($this->user->fresh()->email)->toBe('pat.routespec@example.test');
    });

    it('needs a CSRF token', function () {
        AccountRequests::send('DELETE', '/account/email', [], $this->session, false);

        expect($this->user->fresh()->pending_email)->toBe('new.routespec@example.test');
    });
});
