<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\EmailSuppressions;

uses(TransactionalTestCase::class);

// Confirming an alert does not lift a suppression. Confirmation tokens are
// reused in other links and survive email changes, so following one is not
// fresh proof that the address receives mail. Suppressions are lifted by hand.
describe('ALERT::confirm() for a suppressed address', function () {
    beforeEach(function () {
        $this->alert_id = AlertFixtures::alert('alice@example.invalid', 'speaker:10001', false, false, 'tokenexample1')->alert_id;
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
    });

    it('confirms the alert', function () {
        expect((new ALERT())->confirm($this->alert_id . '-tokenexample1'))->toBeTrue();
    });

    it('leaves the suppression in place', function () {
        (new ALERT())->confirm($this->alert_id . '-tokenexample1');

        expect(EmailSuppressions::isSuppressed('alice@example.invalid'))->toBeTrue();
    });
});

describe('ALERT::confirm() with a link saved before a suppression', function () {
    beforeEach(function () {
        $this->alert_id = AlertFixtures::alert('alice@example.invalid', 'speaker:10001', false, false, 'tokenexample2')->alert_id;
        (new ALERT())->confirm($this->alert_id . '-tokenexample2');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
    });

    it('still succeeds', function () {
        expect((new ALERT())->confirm($this->alert_id . '-tokenexample2'))->toBeTrue();
    });

    it('does not lift the suppression', function () {
        (new ALERT())->confirm($this->alert_id . '-tokenexample2');

        expect(EmailSuppressions::isSuppressed('alice@example.invalid'))->toBeTrue();
    });
});
