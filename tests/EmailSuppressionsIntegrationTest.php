<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';

use Illuminate\Database\UniqueConstraintViolationException;
use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Models\EmailSuppression;

/**
 * Suppression records and the send guard. Uses fictional addresses only, and
 * a capturing transport so nothing is sent.
 */
class EmailSuppressionsIntegrationTest extends TransactionalTestCase {

    /**
     * @var array
     */
    private $sent = [];

    /**
     * The page object before the test, restored afterwards, because tests
     * share one PHP process and one test replaces it with a stub.
     *
     * @var array|null
     */
    private $previousPage = null;

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        $this->previousPage = array_key_exists('PAGE', $GLOBALS) ? [$GLOBALS['PAGE']] : null;
        if (!defined('CONTACTEMAIL')) {
            define('CONTACTEMAIL', 'contact@example.invalid');
        }
        $this->sent = [];
        set_mail_transport(function ($to, $subject, $message, $headers) {
            $this->sent[] = $to;
            return true;
        });
    }

    /**
     *
     */
    protected function tearDown(): void {
        set_mail_transport(null);
        if ($this->previousPage === null) {
            unset($GLOBALS['PAGE']);
        } else {
            $GLOBALS['PAGE'] = $this->previousPage[0];
        }
        parent::tearDown();
    }

    /**
     *
     */
    public function test_normalise_lowercases_trims_and_strips_a_display_name() {
        $this->assertSame('alice@example.invalid', EmailSuppressions::normalise("  Alice@Example.INVALID \n"));
        $this->assertSame('alice@example.invalid', EmailSuppressions::normalise('Alice Example <Alice@Example.invalid>'));
    }

    /**
     *
     */
    public function test_an_address_is_not_suppressed_until_it_is_suppressed() {
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_suppress_records_the_cause_and_matches_any_case() {
        $this->assertTrue(EmailSuppressions::suppress('Alice@Example.invalid', 'hard_bounce', 'MessageBounced', '550 5.1.1 no such user'));

        $this->assertTrue(EmailSuppressions::isSuppressed(' ALICE@example.invalid '));
        $row = EmailSuppression::where('email', 'alice@example.invalid')->first();
        $this->assertSame('hard_bounce', $row->reason);
        $this->assertSame('MessageBounced', $row->postal_event);
        $this->assertSame('550 5.1.1 no such user', $row->reply_excerpt);
        $this->assertNotNull($row->suppressed_at);
        $this->assertNull($row->lifted_at);
    }

    /**
     *
     */
    public function test_a_repeated_suppression_changes_nothing() {
        $this->assertTrue(EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce'));
        $this->assertFalse(EmailSuppressions::suppress('ALICE@example.invalid', 'hard_bounce'));

        $this->assertSame(1, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_a_long_reply_is_cut_to_fit() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, str_repeat('x', 1000));
        $row = EmailSuppression::where('email', 'alice@example.invalid')->first();
        $this->assertSame(255, strlen($row->reply_excerpt));
    }

    /**
     *
     */
    public function test_lifting_keeps_the_row_and_a_later_bounce_suppresses_afresh() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
        $this->assertSame(1, EmailSuppressions::lift('Alice@example.invalid'));

        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
        $this->assertNotNull(EmailSuppression::where('email', 'alice@example.invalid')->first()->lifted_at);

        $this->assertTrue(EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce'));
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
        $this->assertSame(2, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     * The database itself refuses a second active suppression, so two requests
     * racing to suppress one address can't leave two.
     */
    public function test_the_database_allows_only_one_active_suppression_per_address() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $this->expectException(UniqueConstraintViolationException::class);
        EmailSuppression::create([
            'email' => 'alice@example.invalid',
            'reason' => 'hard_bounce',
            'suppressed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     *
     */
    public function test_lifting_an_address_that_is_not_suppressed_does_nothing() {
        $this->assertSame(0, EmailSuppressions::lift('nobody@example.invalid'));
    }

    /**
     *
     */
    public function test_an_alert_email_to_a_suppressed_address_is_not_sent() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $this->assertTrue(send_email('Alice@Example.invalid', 'Alerts', 'Body', true, 'alert'));

        $this->assertSame([], $this->sent);
    }

    /**
     *
     */
    public function test_other_kinds_of_email_to_a_suppressed_address_are_sent() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        foreach (['confirmation', 'password', 'notice'] as $kind) {
            send_email('alice@example.invalid', 'Hello', 'Body', false, $kind);
        }

        $this->assertCount(3, $this->sent);
    }

    /**
     *
     */
    public function test_an_alert_email_to_an_address_that_is_not_suppressed_is_sent() {
        send_email('bob@example.invalid', 'Alerts', 'Body', true, 'alert');
        $this->assertSame(['bob@example.invalid'], $this->sent);
    }

    /**
     *
     */
    public function test_a_lifted_suppression_lets_alerts_through_again() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
        EmailSuppressions::lift('alice@example.invalid');

        send_email('alice@example.invalid', 'Alerts', 'Body', true, 'alert');

        $this->assertSame(['alice@example.invalid'], $this->sent);
    }

    /**
     * Mail built from the alert_mailout template, as the daily run and the
     * gone-MPs script send it, must hit the guard.
     */
    public function test_a_templated_alert_mailout_to_a_suppressed_address_is_not_sent() {
        $GLOBALS['PAGE'] = new class {

            /**
             *
             */
            public function error_message($m) {
                throw new RuntimeException($m);
            }

        };
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');

        $this->assertTrue(send_template_email(['to' => 'alice@example.invalid', 'template' => 'alert_mailout'], ['DATA' => 'x'], true));

        $this->assertSame([], $this->sent);
    }

}
