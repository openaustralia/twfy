<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Models\EmailSuppression;
use OpenAustralia\TWFY\SuppressionImport;

/**
 * The one-off import of addresses Postal's history says have hard-failed.
 * Fictional addresses only.
 */
class SuppressionImportIntegrationTest extends TransactionalTestCase {

    /**
     * @var string[]
     */
    private $files = [];

    /**
     *
     */
    protected function tearDown(): void {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /**
     *
     */
    private function csv(string $contents): string {
        $path = tempnam(sys_get_temp_dir(), 'twfy-import-test');
        file_put_contents($path, $contents);
        $this->files[] = $path;
        return $path;
    }

    /**
     *
     */
    public function test_a_hard_failed_address_is_suppressed_with_the_reason_and_the_date_of_the_failure() {
        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,2026-03-04,550 5.1.1 no such user\n"));

        $this->assertSame(1, $counts['imported']);
        $row = EmailSuppression::where('email', 'alice@example.invalid')->first();
        $this->assertSame('imported_from_postal_history', $row->reason);
        $this->assertSame('550 5.1.1 no such user', $row->reply_excerpt);
        $this->assertStringStartsWith('2026-03-04', $row->suppressed_at);
    }

    /**
     * A 5.7.x refusal blames our server and says nothing about the address.
     */
    public function test_refusals_that_blame_our_server_are_skipped() {
        $counts = SuppressionImport::fromFile($this->csv(
            "address,date,reply\n" .
            "alice@example.invalid,2026-03-04,550 5.7.1 sender blocked\n" .
            "bob@example.invalid,2026-03-04,550-5.7.26 unauthenticated\n"
        ));

        $this->assertSame(0, $counts['imported']);
        $this->assertSame(2, $counts['skipped_blames_our_server']);
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * A failure with no reply from the receiving server isn't proof of a dead
     * address.
     */
    public function test_rows_with_no_reply_from_the_receiving_server_are_skipped() {
        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,2026-03-04,\nbob@example.invalid,2026-03-04,   \n"));

        $this->assertSame(0, $counts['imported']);
        $this->assertSame(2, $counts['skipped_no_reply']);
        $this->assertFalse(EmailSuppressions::isSuppressed('bob@example.invalid'));
    }

    /**
     *
     */
    public function test_running_it_twice_changes_nothing_the_second_time() {
        $file = $this->csv("address,date,reply\nalice@example.invalid,2026-03-04,550 5.1.1 no such user\n");

        SuppressionImport::fromFile($file);
        $second = SuppressionImport::fromFile($file);

        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, $second['already_suppressed']);
        $this->assertSame(1, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     * Someone whose suppression was lifted, by confirming a link or by staff,
     * must not be suppressed again by re-running the import on the same file.
     */
    public function test_a_lifted_address_is_not_suppressed_again_by_the_same_old_failure() {
        $file = $this->csv("address,date,reply\nalice@example.invalid,2026-03-04,550 5.1.1 no such user\n");
        SuppressionImport::fromFile($file);
        EmailSuppressions::lift('alice@example.invalid');

        $again = SuppressionImport::fromFile($file);

        $this->assertSame(0, $again['imported']);
        $this->assertSame(1, $again['skipped_already_lifted']);
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * A failure after the lift is new evidence, so it still counts.
     */
    public function test_a_failure_after_a_lift_is_still_imported() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, null, '2026-01-01 00:00:00');
        EmailSuppressions::lift('alice@example.invalid');
        $future = date('Y-m-d', strtotime('+1 day'));

        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,$future,550 5.1.1 no such user\n"));

        $this->assertSame(1, $counts['imported']);
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * With no readable date we can't tell whether the failure came before the
     * lift, so we leave an address someone restored alone.
     */
    public function test_a_lifted_address_with_an_unreadable_date_is_left_alone() {
        EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
        EmailSuppressions::lift('alice@example.invalid');

        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,garbage,550 5.1.1 no such user\n"));

        $this->assertSame(1, $counts['skipped_already_lifted']);
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_addresses_are_normalised_and_unusable_rows_are_counted() {
        $counts = SuppressionImport::fromFile($this->csv(
            "address,date,reply\n" .
            "  Alice@Example.INVALID ,2026-03-04,550 5.1.1 no such user\n" .
            ",2026-03-04,550 5.1.1 no such user\n" .
            "not an address,2026-03-04,550 5.1.1 no such user\n"
        ));

        $this->assertSame(1, $counts['imported']);
        $this->assertSame(2, $counts['invalid']);
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * A bad date shouldn't lose a suppression we know is right.
     */
    public function test_an_unreadable_date_falls_back_to_now() {
        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,garbage,550 5.1.1 no such user\n"));

        $this->assertSame(1, $counts['imported']);
        $this->assertStringStartsWith(date('Y-m-d'), EmailSuppression::where('email', 'alice@example.invalid')->first()->suppressed_at);
    }

    /**
     *
     */
    public function test_a_file_without_a_header_row_is_read_too() {
        $counts = SuppressionImport::fromFile($this->csv("alice@example.invalid,2026-03-04,550 5.1.1 no such user\n"));

        $this->assertSame(1, $counts['imported']);
    }

    /**
     *
     */
    public function test_an_unreadable_file_is_an_error_not_an_empty_import() {
        $this->expectException(RuntimeException::class);
        SuppressionImport::fromFile('/no/such/file.csv');
    }

    /**
     * The counts are all the script prints, so they must never carry an address.
     */
    public function test_counts_hold_only_numbers() {
        $counts = SuppressionImport::fromFile($this->csv("address,date,reply\nalice@example.invalid,2026-03-04,550 5.1.1 no such user\n"));

        foreach ($counts as $value) {
            $this->assertIsInt($value);
        }
    }

}
