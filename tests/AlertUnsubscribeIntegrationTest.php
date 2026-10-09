<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * One-click unsubscribe: every alert at an address, authorised by the token of
 * one of them, so no login is needed.
 */
class AlertUnsubscribeIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        if (!defined('DOMAIN')) {
            define('DOMAIN', 'example.org');
        }
        if (!defined('WEBPATH')) {
            define('WEBPATH', '/');
        }
    }

    /**
     *
     */
    private function insertAlert(string $email, string $criteria, string $token, int $deleted = 0): int {
        $q = parlDBQuery(
            "INSERT INTO alerts (email, criteria, registrationtoken, confirmed, deleted, created) VALUES (?, ?, ?, 1, ?, NOW())",
            $email,
            $criteria,
            $token,
            $deleted
        );
        return (int) $q->insert_id();
    }

    /**
     *
     */
    private function deleted(int $id): bool {
        return (int) parlDBQuery('SELECT deleted FROM alerts WHERE alert_id = ?', $id)->field(0, 'deleted') === 1;
    }

    /**
     *
     */
    public function test_one_alerts_token_removes_every_alert_at_the_address() {
        $a = $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');
        $b = $this->insertAlert('alice@example.invalid', 'climate', 'tokenb');
        $c = $this->insertAlert('Alice@Example.invalid', 'budget', 'tokenc');

        $removed = (new ALERT())->delete_all_at_address($a . '-tokena');

        $this->assertSame(3, $removed);
        $this->assertTrue($this->deleted($a));
        $this->assertTrue($this->deleted($b));
        $this->assertTrue($this->deleted($c));
    }

    /**
     *
     */
    public function test_other_addresses_alerts_are_left_alone() {
        $a = $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');
        $bob = $this->insertAlert('bob@example.invalid', 'speaker:1', 'tokenbob');

        (new ALERT())->delete_all_at_address($a . '-tokena');

        $this->assertFalse($this->deleted($bob));
    }

    /**
     *
     */
    public function test_a_wrong_or_malformed_token_removes_nothing() {
        $a = $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');

        foreach ([$a . '-wrongtoken', 'nonsense', '', $a . '-', '999999-tokena', '-tokena'] as $token) {
            $this->assertFalse((new ALERT())->delete_all_at_address($token), $token);
        }

        $this->assertFalse($this->deleted($a));
    }

    /**
     * get_http_var() returns an array for ?t[]=x, which used to throw a
     * TypeError instead of being refused.
     */
    public function test_a_token_that_is_not_a_string_is_refused() {
        $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');

        foreach ([['bad'], null, 12, false] as $token) {
            $this->assertNull((new ALERT())->email_for_token($token));
            $this->assertFalse((new ALERT())->delete_all_at_address($token));
        }
    }

    /**
     * Alerts already removed aren't counted again.
     */
    public function test_already_removed_alerts_are_not_counted() {
        $a = $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');
        $this->insertAlert('alice@example.invalid', 'climate', 'tokenb', 1);

        $this->assertSame(1, (new ALERT())->delete_all_at_address($a . '-tokena'));
    }

    /**
     * Someone who unsubscribes twice, or whose mail app retries, still gets a
     * success, because the alert exists and the token is right.
     */
    public function test_repeating_it_is_harmless() {
        $a = $this->insertAlert('alice@example.invalid', 'speaker:1', 'tokena');

        (new ALERT())->delete_all_at_address($a . '-tokena');
        $this->assertSame(0, (new ALERT())->delete_all_at_address($a . '-tokena'));
    }

    /**
     *
     */
    public function test_the_unsubscribe_url_is_an_https_link_with_the_alerts_token() {
        $url = ALERT::unsubscribe_url(12, 'abc123');

        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('alert/unsubscribe/?t=12-abc123', $url);
    }

}
