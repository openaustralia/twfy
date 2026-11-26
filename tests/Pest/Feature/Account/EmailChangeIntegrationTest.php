<?php

/**
 * @file
 * Changing the email address on an account, through the link emailed to the new address. Uses fictional users.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/AccountRequests.php';

use OpenAustralia\TWFY\Account\EmailChange;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

defined('CONTACTEMAIL') || define('CONTACTEMAIL', 'contact@example.test');
defined('DOMAIN') || define('DOMAIN', 'example.test');

beforeEach(function () {
    $this->sent = [];
    $GLOBALS['mail_transport'] = function (string $to, string $subject, string $message) {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'message' => $message];
        return true;
    };

    $this->change = new EmailChange();
    $this->user = User::create([
        'firstname' => 'Pat',
        'lastname' => 'Changespec',
        'email' => 'pat.changespec@example.test',
        'password' => 'stored-hash',
        'confirmed' => true,
    ]);
    $this->hashFor = fn (User $user) => (string) preg_replace('~.*/([0-9a-f]{64})\?.*~', '$1', $this->change->confirmationUrl($user));
});

afterEach(function () {
    unset($GLOBALS['mail_transport']);
    laravel_spec_new_request();
});

describe('EmailChange', function () {
    describe('request()', function () {
        beforeEach(fn () => $this->change->request($this->user, 'new.changespec@example.test'));

        it('keeps the new address waiting', function () {
            expect($this->user->fresh()->pending_email)->toBe('new.changespec@example.test');
        });

        it('leaves the current address alone', function () {
            expect($this->user->fresh()->email)->toBe('pat.changespec@example.test');
        });

        it('emails a link to the new address', function () {
            expect($this->sent[0])->toMatchArray(['to' => 'new.changespec@example.test'])
              ->and($this->sent[0]['message'])->toContain('/account/email/confirm/' . $this->user->user_id . '/');
        });

        it('tells the current address', function () {
            expect($this->sent[1])->toMatchArray(['to' => 'pat.changespec@example.test']);
        });

        it('keeps the link out of the notice to the current address', function () {
            expect($this->sent[1]['message'])->not->toContain('/email/confirm/');
        });

        it('sends nothing else', function () {
            expect($this->sent)->toHaveCount(2);
        });
    });

    describe('confirm()', function () {
        beforeEach(function () {
            $this->change->request($this->user, 'new.changespec@example.test');
            $this->sent = [];
            $this->hash = ($this->hashFor)($this->user);
        });

        describe('with the link for the waiting address', function () {
            it('changes the address', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->user->fresh()->email)->toBe('new.changespec@example.test');
            });

            it('says it changed', function () {
                expect($this->change->confirm($this->user, $this->hash))->toBeTrue();
            });

            it('stops waiting', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->user->fresh()->pending_email)->toBeNull();
            });

            it('moves the alerts to the new address', function () {
                $alert = Alert::create(['email' => 'pat.changespec@example.test', 'criteria' => 'changespec', 'registrationtoken' => 'x', 'confirmed' => true]);

                $this->change->confirm($this->user, $this->hash);

                expect($alert->fresh()->email)->toBe('new.changespec@example.test');
            });

            it('leaves other addresses alerts alone', function () {
                $alert = Alert::create(['email' => 'other.changespec@example.test', 'criteria' => 'changespec', 'registrationtoken' => 'x', 'confirmed' => true]);

                $this->change->confirm($this->user, $this->hash);

                expect($alert->fresh()->email)->toBe('other.changespec@example.test');
            });

            it('tells the old address', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->sent)->toHaveCount(1)
                  ->and($this->sent[0]['to'])->toBe('pat.changespec@example.test')
                  ->and($this->sent[0]['message'])->toContain('new.changespec@example.test');
            });

            it('signs out other browsers', function () {
                $other = AccountRequests::signIn($this->user);

                $this->change->confirm($this->user, $this->hash);

                expect(Session::find($other['id']))->toBeNull();
            });

            it('keeps the browser that confirmed', function () {
                $mine = AccountRequests::signIn($this->user);

                $this->change->confirm($this->user, $this->hash, $mine['id']);

                expect(Session::find($mine['id']))->not->toBeNull();
            });

            it('works only once', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->change->confirm($this->user, $this->hash))->toBeFalse();
            });
        });

        describe('with a link for an address that was replaced', function () {
            beforeEach(function () {
                $this->change->request($this->user, 'newer.changespec@example.test');
            });

            it('refuses it', function () {
                expect($this->change->confirm($this->user, $this->hash))->toBeFalse();
            });

            it('keeps the account address', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->user->fresh()->email)->toBe('pat.changespec@example.test');
            });

            it('still takes the newer link', function () {
                expect($this->change->confirm($this->user, ($this->hashFor)($this->user)))->toBeTrue();
            });
        });

        describe('with a made-up hash', function () {
            it('refuses it', function () {
                expect($this->change->confirm($this->user, str_repeat('0', 64)))->toBeFalse();
            });
        });

        describe('when someone else has joined with the address since', function () {
            beforeEach(function () {
                User::create(['firstname' => 'Quinn', 'lastname' => 'Changespec', 'email' => 'new.changespec@example.test', 'password' => 'stored-hash']);
            });

            it('refuses it', function () {
                expect($this->change->confirm($this->user, $this->hash))->toBeFalse();
            });

            it('keeps the account address', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->user->fresh()->email)->toBe('pat.changespec@example.test');
            });

            it('stops waiting', function () {
                $this->change->confirm($this->user, $this->hash);

                expect($this->user->fresh()->pending_email)->toBeNull();
            });
        });

        describe('without a change waiting', function () {
            it('refuses it', function () {
                $this->change->cancel($this->user);

                expect($this->change->confirm($this->user, $this->hash))->toBeFalse();
            });
        });
    });

    describe('resend()', function () {
        it('emails the link to the waiting address again', function () {
            $this->change->request($this->user, 'new.changespec@example.test');
            $this->sent = [];

            $this->change->resend($this->user);

            expect(array_column($this->sent, 'to'))->toBe(['new.changespec@example.test']);
        });

        it('sends nothing when no change is waiting', function () {
            $this->change->resend($this->user);

            expect($this->sent)->toBe([]);
        });
    });

    describe('cancel()', function () {
        it('stops waiting', function () {
            $this->change->request($this->user, 'new.changespec@example.test');

            $this->change->cancel($this->user);

            expect($this->user->fresh()->pending_email)->toBeNull();
        });
    });

    describe('confirmationUrl()', function () {
        it('is signed', function () {
            $this->change->request($this->user, 'new.changespec@example.test');

            expect($this->change->confirmationUrl($this->user))->toContain('signature=')->toContain('expires=');
        });

        it('does not hold the address', function () {
            $this->change->request($this->user, 'new.changespec@example.test');

            expect($this->change->confirmationUrl($this->user))->not->toContain('changespec');
        });
    });
});
