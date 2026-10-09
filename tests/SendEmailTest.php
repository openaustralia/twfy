<?php

/**
 * @file
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';

/**
 * Behaviour of send_email() and send_template_email() as seen through the
 * injectable mail transport. Nothing here sends real mail.
 */
class SendEmailTest extends TestCase {

    /**
     * @var array
     */
    private $sent = [];

    /**
     * The page object before the test, restored afterwards, because tests
     * share one PHP process and some replace it with a stub.
     *
     * @var array|null
     */
    private $previousPage = null;

    /**
     *
     */
    protected function setUp(): void {
        $this->previousPage = array_key_exists('PAGE', $GLOBALS) ? [$GLOBALS['PAGE']] : null;
        if (!defined('CONTACTEMAIL')) {
            define('CONTACTEMAIL', 'contact@example.invalid');
        }
        $this->sent = [];
        set_mail_transport(function ($to, $subject, $message, $headers) {
            $this->sent[] = compact('to', 'subject', 'message', 'headers');
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
    }

    /**
     *
     */
    public function test_send_email_hands_recipient_subject_and_headers_to_the_transport() {
        $this->assertTrue(send_email('alice@example.invalid', 'Hello', 'Body text'));

        $this->assertCount(1, $this->sent);
        $this->assertSame('alice@example.invalid', $this->sent[0]['to']);
        $this->assertSame('Hello', $this->sent[0]['subject']);
        $this->assertSame('Body text', $this->sent[0]['message']);
        $this->assertStringContainsString('From: OpenAustralia.org <' . CONTACTEMAIL . '>', $this->sent[0]['headers']);
    }

    /**
     *
     */
    public function test_send_email_returns_the_transport_result() {
        set_mail_transport(function () {
            return false;
        });
        $this->assertFalse(send_email('alice@example.invalid', 'Hello', 'Body text'));
    }

    /**
     *
     */
    public function test_bulk_mail_is_marked_with_a_precedence_header() {
        send_email('alice@example.invalid', 'Hello', 'Body text', true);
        $this->assertStringContainsString('Precedence: bulk', $this->sent[0]['headers']);
    }

    /**
     *
     */
    #[DataProvider('templateKinds')]
    public function test_each_template_is_classified_into_one_kind($template, $kind) {
        $this->assertSame($kind, mail_kind_for_template($template));
    }

    /**
     * Every template in the emails folder must be classified deliberately, so
     * a new template can't silently become a notice.
     */
    public function test_every_email_template_is_covered_by_the_kind_table() {
        $known = array_column(iterator_to_array($this->templateKinds()), 0);
        $files = glob(__DIR__ . '/../www/includes/easyparliament/templates/emails/*.txt');
        $templates = array_diff(array_map(fn($f) => basename($f, '.txt'), $files), ['_ReadMe']);
        $this->assertSame([], array_values(array_diff($templates, $known)));
    }

    /**
     * The daily alert run and the gone-MPs script both use alert_mailout and
     * must stay classified as alert, or the send guard would let their mail
     * through to suppressed addresses.
     */
    public function test_the_alert_mailout_template_is_an_alert() {
        $this->assertSame('alert', mail_kind_for_template('alert_mailout'));
    }

    /**
     *
     */
    public function test_direct_send_email_callers_default_to_a_notice() {
        $this->assertSame('notice', mail_kind_for_template('no_such_template'));
    }

    /**
     *
     */
    public function test_template_email_reaches_the_transport_with_merged_text() {
        $GLOBALS['PAGE'] = new class {

            /**
             *
             */
            public function error_message($m) {
                throw new RuntimeException($m);
            }

        };
        $ok = send_template_email(
            ['to' => 'alice@example.invalid', 'template' => 'new_password'],
            ['EMAIL' => 'alice@example.invalid', 'LOGINURL' => 'https://example.invalid/login', 'PASSWORD' => 'secret-example']
        );
        $this->assertTrue($ok);
        $this->assertSame('alice@example.invalid', $this->sent[0]['to']);
        $this->assertStringContainsString('secret-example', $this->sent[0]['message']);
    }

    /**
     *
     */
    public static function templateKinds() {
        yield ['alert_mailout', 'alert'];
        yield ['alert_confirmation', 'confirmation'];
        yield ['join_confirmation', 'confirmation'];
        yield ['new_password', 'password'];
        yield ['comment_deleted', 'notice'];
        yield ['comment_deleted_blank', 'notice'];
        yield ['email_a_friend', 'notice'];
        yield ['report_acknowledge', 'notice'];
        yield ['report_declined', 'notice'];
        yield ['report_upheld', 'notice'];
    }

}
