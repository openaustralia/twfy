<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../www/includes/easyparliament/recess.php';

/**
 * Tests for recess_prettify.
 */
class RecessTest extends TestCase {

    /**
     * A date inside a known recess should return recess details.
     */
    public function test_date_inside_recess_returns_recess_range(): void {
        $result = recess_prettify(10, 4, 2026, 1);

        $this->assertSame(['recess', '2026-04-02', '2026-05-11'], $result);
    }

    /**
     * Recess boundaries should be inclusive.
     */
    public function test_recess_boundaries_are_inclusive(): void {
        $start = recess_prettify(2, 4, 2026, 1);
        $end = recess_prettify(11, 5, 2026, 1);

        $this->assertSame(['recess', '2026-04-02', '2026-05-11'], $start);
        $this->assertSame(['recess', '2026-04-02', '2026-05-11'], $end);
    }

    /**
     * A date outside recess should return a fixed-shape null triplet.
     */
    public function test_date_outside_recess_returns_null_triplet(): void {
        $result = recess_prettify(12, 5, 2026, 1);

        $this->assertSame([null, null, null], $result);
    }

    /**
     * Unknown body ids should not emit warnings and should return null triplet.
     */
    public function test_unknown_body_returns_null_triplet(): void {
        $result = recess_prettify(10, 4, 2026, 999);

        $this->assertSame([null, null, null], $result);
    }

}
