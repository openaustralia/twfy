<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\EmailSuppressions;

/**
 * Confirming an alert lifts a suppression on its address.
 */
class AlertConfirmIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    private function insertAlert(string $email, string $token): int {
        // The legacy ALERT class reads over the mysqli connection, so insert
        // over that same connection.
        $q = parlDBQuery(
            "INSERT INTO alerts (email, criteria, registrationtoken, confirmed, deleted, created) VALUES (?, 'speaker:10001', ?, 0, 0, NOW())",
            $email,
            $token
        );
        return (int) $q->insert_id();
    }

    /**
     *
     */
    public function test_confirming_an_alert_lifts_a_suppression_on_its_address() {
        $alertId = $this->insertAlert('alice@example.invalid', 'tokenexample1');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $alert = new ALERT();
        $this->assertTrue($alert->confirm($alertId . '-tokenexample1'));

        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_a_wrong_token_does_not_lift_the_suppression() {
        $alertId = $this->insertAlert('alice@example.invalid', 'tokenexample2');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $alert = new ALERT();
        $this->assertFalse($alert->confirm($alertId . '-wrongtoken'));

        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * Resuming leaves the person's other suppressed neighbours alone.
     */
    public function test_confirming_lifts_only_that_address() {
        $alertId = $this->insertAlert('alice@example.invalid', 'tokenexample3');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
        EmailSuppressions::suppress('bob@example.invalid', 'hard_bounce');

        (new ALERT())->confirm($alertId . '-tokenexample3');

        $this->assertTrue(EmailSuppressions::isSuppressed('bob@example.invalid'));
    }

}
