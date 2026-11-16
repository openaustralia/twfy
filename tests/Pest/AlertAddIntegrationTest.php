<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\Models\Alert as AlertModel;

uses(TransactionalTestCase::class);

beforeEach(function () {
    if (!defined('CONTACTEMAIL')) {
        define('CONTACTEMAIL', 'contact@example.invalid');
    }
    if (!defined('DOMAIN')) {
        define('DOMAIN', 'example.org');
    }
    $this->mailWorks = true;
    $this->sent = [];
    set_mail_transport(function ($to, $subject, $message, $headers) {
        $this->sent[] = $to;
        return $this->mailWorks;
    });
});

afterEach(function () {
    set_mail_transport(null);
});

describe('ALERT::add()', function () {
    describe('with a new speaker alert', function () {
        beforeEach(function () {
            $this->alert = new ALERT();
            $this->result = $this->alert->add(AlertFixtures::details());
            $this->saved = AlertModel::find($this->alert->alert_id);
        });

        it('returns 1', function () {
            expect($this->result)->toBe(1);
        });

        it('stores the speaker criteria', function () {
            expect($this->saved->criteria)->toBe('speaker:10001');
        });

        it('confirms it straight away', function () {
            expect($this->saved->confirmed)->toBeTrue();
        });

        it('leaves it undeleted', function () {
            expect($this->saved->deleted)->toBeFalse();
        });

        it('does not mark it recommended', function () {
            expect($this->saved->recommended)->toBeFalse();
        });

        it('keeps the registration token', function () {
            expect($this->saved->registrationtoken)->toBe($this->alert->registrationtoken);
        });

        it('makes the token url-safe', function () {
            expect($this->alert->registrationtoken)->toMatch('/^[A-Za-z0-9_]{22}$/');
        });

        it('stamps created from the database clock', function () {
            $close = AlertModel::whereKey($this->alert->alert_id)
              ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, created, NOW())) < 2')
              ->exists();

            expect($close)->toBeTrue();
        });

        it('stamps created_at from the database clock', function () {
            $close = AlertModel::whereKey($this->alert->alert_id)
              ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, created_at, NOW())) < 2')
              ->exists();

            expect($close)->toBeTrue();
        });

        it('remembers the criteria', function () {
            expect($this->alert->criteria)->toBe('speaker:10001');
        });
    });

    describe('with a keyword', function () {
        it('stores the keyword as the criteria', function () {
            $alert = new ALERT();
            $alert->add(AlertFixtures::details(['pid' => '', 'keyword' => 'budget']));

            expect(AlertModel::find($alert->alert_id)->criteria)->toBe('budget');
        });
    });

    describe('with a recommendation', function () {
        it('marks it recommended', function () {
            $alert = new ALERT();
            $alert->add(AlertFixtures::details(['recommended' => 1]));

            expect(AlertModel::find($alert->alert_id)->recommended)->toBeTrue();
        });
    });

    describe('without instant confirmation', function () {
        beforeEach(function () {
            $this->alert = new ALERT();
            $this->result = $this->alert->add(AlertFixtures::details(), false, false);
        });

        it('leaves it unconfirmed', function () {
            expect(AlertModel::find($this->alert->alert_id)->confirmed)->toBeFalse();
        });

        it('returns nothing', function () {
            expect($this->result)->toBeNull();
        });
    });

    describe('with a confirmation email', function () {
        it('returns 1', function () {
            expect((new ALERT())->add(AlertFixtures::details(['pid' => '', 'keyword' => 'budget']), true))->toBe(1);
        });

        it('sends the email to the address', function () {
            (new ALERT())->add(AlertFixtures::details(['pid' => '', 'keyword' => 'budget']), true);

            expect($this->sent)->toBe(['alice@example.invalid']);
        });

        it('leaves it unconfirmed', function () {
            $alert = new ALERT();
            $alert->add(AlertFixtures::details(['pid' => '', 'keyword' => 'budget']), true);

            expect(AlertModel::find($alert->alert_id)->confirmed)->toBeFalse();
        });

        describe('when the email cannot be sent', function () {
            it('returns -1', function () {
                $this->mailWorks = false;

                expect((new ALERT())->add(AlertFixtures::details(['pid' => '', 'keyword' => 'budget']), true))->toBe(-1);
            });
        });
    });

    describe('when the alert is already active', function () {
        beforeEach(function () {
            AlertFixtures::alert('alice@example.invalid', 'speaker:10001');
            $this->result = (new ALERT())->add(AlertFixtures::details());
        });

        it('returns -2', function () {
            expect($this->result)->toBe(-2);
        });

        it('adds no second alert', function () {
            expect(AlertModel::where('email', 'alice@example.invalid')->count())->toBe(1);
        });
    });

    describe('when the alert was deleted', function () {
        beforeEach(function () {
            $this->existing = AlertFixtures::alert('alice@example.invalid', 'speaker:10001', true, true);
            $this->result = (new ALERT())->add(AlertFixtures::details());
        });

        it('returns 1', function () {
            expect($this->result)->toBe(1);
        });

        it('restores the alert', function () {
            expect($this->existing->fresh()->deleted)->toBeFalse();
        });

        it('adds no second alert', function () {
            expect(AlertModel::where('email', 'alice@example.invalid')->count())->toBe(1);
        });
    });

    describe('when the database rejects the alert', function () {
        it('returns -1', function () {
            $too_long = str_repeat('a', 300) . '@example.invalid';

            expect((new ALERT())->add(AlertFixtures::details(['email' => $too_long])))->toBe(-1);
        });
    });
});
