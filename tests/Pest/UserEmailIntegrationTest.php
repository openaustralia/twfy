<?php

/**
 * @file
 * Looking up addresses and emailing users from templates. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';

use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

defined('CONTACTEMAIL') || define('CONTACTEMAIL', 'contact@example.test');

beforeEach(function () {
    $this->sent = [];
    $GLOBALS['mail_transport'] = function (string $to, string $subject, string $message) {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'message' => $message];
        return true;
    };
    $this->user = User::create(['firstname' => 'Pat', 'lastname' => 'Mailspec', 'email' => 'pat.mailspec@example.test', 'password' => 'stored-hash']);
});

afterEach(function () {
    unset($GLOBALS['mail_transport']);
});

describe('User', function () {
    describe('emailTaken()', function () {
        it('is true for an address a user has', function () {
            expect(User::emailTaken('pat.mailspec@example.test'))->toBeTrue();
        });

        it('is true in other letters', function () {
            expect(User::emailTaken('PAT.mailspec@example.test'))->toBeTrue();
        });

        it('is false for another address', function () {
            expect(User::emailTaken('nobody.mailspec@example.test'))->toBeFalse();
        });

        it('is false for the address of the user it excepts', function () {
            expect(User::emailTaken('pat.mailspec@example.test', $this->user->user_id))->toBeFalse();
        });

        it('is true for a deleted user', function () {
            $this->user->update(['deleted' => true]);

            expect(User::emailTaken('pat.mailspec@example.test'))->toBeTrue();
        });
    });

    describe('sendTemplate()', function () {
        it('emails the user', function () {
            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent[0]['to'])->toBe('pat.mailspec@example.test');
        });

        it('fills in their name', function () {
            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent[0]['message'])->toContain('Hi Pat,');
        });

        it('fills in the contact address', function () {
            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent[0]['message'])->toContain(CONTACTEMAIL);
        });

        it('greets someone with only a last name by it', function () {
            $this->user->update(['firstname' => '', 'lastname' => 'Mailspec']);

            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent[0]['message'])->toContain('Hi Mailspec,');
        });

        it('leaves no token unfilled', function () {
            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent[0]['message'])->not->toMatch('/\{[A-Z]+\}/');
        });

        it('can send to another address', function () {
            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test'], 'other.mailspec@example.test');

            expect($this->sent[0]['to'])->toBe('other.mailspec@example.test');
        });

        it('sends nothing to a deleted user', function () {
            $this->user->update(['deleted' => true]);

            $this->user->sendTemplate('email_changed_notice', ['NEWEMAIL' => 'new.mailspec@example.test']);

            expect($this->sent)->toBe([]);
        });

        it('has a template for every notice', function (string $template) {
            $this->user->sendTemplate($template, ['NEWEMAIL' => 'a@example.test', 'CONFIRMURL' => 'u', 'MINUTES' => '60', 'ACCOUNTURL' => 'u', 'RESETURL' => 'u']);

            expect($this->sent[0]['message'])->not->toMatch('/\{[A-Z]+\}/');
        })->with(['email_change_confirm', 'email_change_notice', 'email_changed_notice', 'password_changed_notice']);
    });

    describe('fullName()', function () {
        it('joins both names', function () {
            expect($this->user->fullName())->toBe('Pat Mailspec');
        });

        it('copes with only a first name', function () {
            $this->user->firstname = 'Pat';
            $this->user->lastname = '';

            expect($this->user->fullName())->toBe('Pat');
        });

        it('copes with only a last name', function () {
            $this->user->firstname = '';
            $this->user->lastname = 'Mailspec';

            expect($this->user->fullName())->toBe('Mailspec');
        });

        it('is empty with neither', function () {
            $this->user->firstname = ' ';
            $this->user->lastname = '';

            expect($this->user->fullName())->toBe('');
        });
    });

    describe('passwordProblem()', function () {
        it('is null for a password of the minimum length', function () {
            expect(User::passwordProblem(str_repeat('a', User::PASSWORD_MIN)))->toBeNull();
        });

        it('is null for a password of the maximum length', function () {
            expect(User::passwordProblem(str_repeat('a', User::PASSWORD_MAX)))->toBeNull();
        });

        it('says why a password one character too long is not allowed', function () {
            expect(User::passwordProblem(str_repeat('a', User::PASSWORD_MAX + 1)))->toBe('Your password can have at most ' . User::PASSWORD_MAX . ' characters');
        });

        it('says why a password one character short is not allowed', function () {
            expect(User::passwordProblem(str_repeat('a', User::PASSWORD_MIN - 1)))->toBe('Your password needs at least ' . User::PASSWORD_MIN . ' characters');
        });
    });
});
