<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\EmailSuppressions;

/**
 * The notice a signed-in person sees on their alerts page when their address
 * is suppressed. The page itself gets a manual check, not an automated one.
 */
class AlertSuppressionNoticeIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        if (!defined('WEBPATH')) {
            define('WEBPATH', '/');
        }
    }

    /**
     *
     */
    public function test_there_is_no_notice_for_an_address_that_is_not_suppressed() {
        $this->assertSame('', alert_suppression_notice('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_the_notice_says_alerts_are_paused_since_when_and_how_to_resume() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, null, '2026-03-04 10:00:00');

        $notice = alert_suppression_notice('Alice@example.invalid');

        $this->assertStringContainsString('returning as undeliverable since 4 March 2026', $notice);
        $this->assertStringContainsString('your alerts are paused', $notice);
        $this->assertStringContainsString('change the email address on your account', $notice);
        $this->assertStringContainsString('href="/alert/"', $notice);
    }

    /**
     * The notice stays neutral and plain, and never shows the reply from the
     * receiving server, which can hold detail about someone else's system.
     */
    public function test_the_notice_does_not_show_the_receiving_servers_reply() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, '550 5.1.1 <script>alert(1)</script>');

        $notice = alert_suppression_notice('alice@example.invalid');

        $this->assertStringNotContainsString('550', $notice);
        $this->assertStringNotContainsString('<script>', $notice);
    }

    /**
     *
     */
    public function test_there_is_no_notice_once_the_suppression_is_lifted() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
        EmailSuppressions::lift('alice@example.invalid');

        $this->assertSame('', alert_suppression_notice('alice@example.invalid'));
    }

    /**
     * Only the person's own address is ever looked at.
     */
    public function test_another_addresss_suppression_is_not_shown() {
        EmailSuppressions::suppress('bob@example.invalid', 'hard_bounce');

        $this->assertSame('', alert_suppression_notice('alice@example.invalid'));
    }

}
