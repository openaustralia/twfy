<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';

use OpenAustralia\TWFY\AlertMailedItems;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Indexbatch;

/**
 * The mailer sends a Hansard row once: alert_mailed_at is NULL until it is sent.
 */
class AlertMailedItemsIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    private function makeRow(int $id, ?string $created, ?string $mailedAt = null, int $major = 1): void {
        $prefix = $major === 101 ? 'lords' : 'debate';
        Hansard::create([
            'epobject_id' => $id,
            'gid' => "uk.org.publicwhip/$prefix/2026-03-01.$id.1",
            'major' => $major,
            'hdate' => '2026-03-01',
            'created' => $created,
            'alert_mailed_at' => $mailedAt,
        ]);
    }

    /**
     *
     */
    private function makeBatch(int $id, string $created): void {
        $batch = new Indexbatch();
        $batch->indexbatch_id = $id;
        $batch->created = $created;
        $batch->save();
    }

    /**
     *
     */
    public function test_an_unsent_row_should_be_mailed() {
        $this->makeRow(910001, '2026-03-01 09:00:00');

        $this->assertTrue(AlertMailedItems::shouldMail(1, '2026-03-01.910001.1'));
    }

    /**
     *
     */
    public function test_a_row_that_was_already_sent_is_not_mailed_again() {
        $this->makeRow(910001, '2026-03-01 09:00:00', '2026-03-01 10:00:00');

        $this->assertFalse(AlertMailedItems::shouldMail(1, '2026-03-01.910001.1'));
    }

    /**
     * Senate gids are stored with a lords prefix.
     */
    public function test_an_unsent_senate_row_should_be_mailed() {
        $this->makeRow(910001, '2026-03-01 09:00:00', null, 101);

        $this->assertTrue(AlertMailedItems::shouldMail(101, '2026-03-01.910001.1'));
    }

    /**
     *
     */
    public function test_a_senate_row_that_was_already_sent_is_not_mailed_again() {
        $this->makeRow(910001, '2026-03-01 09:00:00', '2026-03-01 10:00:00', 101);

        $this->assertFalse(AlertMailedItems::shouldMail(101, '2026-03-01.910001.1'));
    }

    /**
     *
     */
    public function test_a_house_we_do_not_store_gids_for_is_always_mailed() {
        $this->assertTrue(AlertMailedItems::shouldMail(3, '2026-03-01.1.1'));
    }

    /**
     *
     */
    public function test_a_row_that_does_not_exist_is_not_mailed() {
        $this->assertFalse(AlertMailedItems::shouldMail(1, '2026-03-01.1.1'));
    }

    /**
     *
     */
    public function test_marking_stamps_rows_created_before_the_batch_margin() {
        $this->makeBatch(990001, '2026-03-01 12:00:00');
        $this->makeRow(910001, '2026-03-01 09:00:00');

        $count = AlertMailedItems::markMailedThroughBatch(990001, '2026-03-01 12:30:00');

        $this->assertSame(1, $count);
        $this->assertSame('2026-03-01 12:30:00', Hansard::find(910001)->alert_mailed_at->format('Y-m-d H:i:s'));
    }

    /**
     *
     */
    public function test_marking_leaves_rows_created_inside_the_margin_unstamped() {
        $this->makeBatch(990001, '2026-03-01 12:00:00');
        $this->makeRow(910001, '2026-03-01 11:30:00');

        $this->assertSame(0, AlertMailedItems::markMailedThroughBatch(990001));
        $this->assertNull(Hansard::find(910001)->alert_mailed_at);
    }

    /**
     *
     */
    public function test_marking_keeps_the_original_stamp_of_a_sent_row() {
        $this->makeBatch(990001, '2026-03-01 12:00:00');
        $this->makeRow(910001, '2026-03-01 09:00:00', '2026-03-01 10:00:00');

        AlertMailedItems::markMailedThroughBatch(990001, '2026-03-01 12:30:00');

        $this->assertSame('2026-03-01 10:00:00', Hansard::find(910001)->alert_mailed_at->format('Y-m-d H:i:s'));
    }

    /**
     *
     */
    public function test_marking_an_unknown_batch_does_nothing() {
        $this->makeRow(910001, '2026-03-01 09:00:00');

        $this->assertSame(0, AlertMailedItems::markMailedThroughBatch(999999));
    }

}
