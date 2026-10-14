<?php

use OpenAustralia\TWFY\AlertMailedItems;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * The mailer does not email items from debates more than 8 weeks old.
 */
class AlertMailedItemsTest extends TestCase {

    /**
     *
     */
    public function test_a_debate_exactly_8_weeks_old_is_still_emailed() {
        $this->assertTrue(AlertMailedItems::isWithinMaxAge('2026-08-20', '2026-10-15'));
    }

    /**
     *
     */
    public function test_a_debate_older_than_8_weeks_is_not_emailed() {
        $this->assertFalse(AlertMailedItems::isWithinMaxAge('2026-08-19', '2026-10-15'));
    }

    /**
     *
     */
    public function test_a_debate_from_today_is_emailed() {
        $this->assertTrue(AlertMailedItems::isWithinMaxAge('2026-10-15', '2026-10-15'));
    }

    /**
     *
     */
    public function test_today_defaults_to_the_current_date() {
        $this->assertTrue(AlertMailedItems::isWithinMaxAge(date('Y-m-d')));
        $this->assertFalse(AlertMailedItems::isWithinMaxAge(date('Y-m-d', strtotime('-9 weeks'))));
    }

}
