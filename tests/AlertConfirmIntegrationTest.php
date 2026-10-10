<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\EmailSuppressions;

/**
 * Confirming an alert does not lift a suppression. Confirmation tokens are
 * reused in other links and survive email changes, so following one is not
 * fresh proof that the address receives mail. Suppressions are lifted by hand.
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
    public function test_confirming_an_alert_leaves_a_suppression_in_place() {
        $alertId = $this->insertAlert('alice@example.invalid', 'tokenexample1');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $alert = new ALERT();
        $this->assertTrue($alert->confirm($alertId . '-tokenexample1'));

        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * An old link saved before the address was suppressed.
     */
    public function test_reusing_a_confirmation_link_after_a_suppression_does_not_lift_it() {
        $alertId = $this->insertAlert('alice@example.invalid', 'tokenexample2');
        (new ALERT())->confirm($alertId . '-tokenexample2');
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        (new ALERT())->confirm($alertId . '-tokenexample2');

        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

}
