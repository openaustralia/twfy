<?php

/**
 * @file
 * Integration tests for pc.php gadget script requiring database access.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for pc.php gadget requiring database.
 */
class PcGadgetIntegrationTest extends TestCase {

    protected $db;

    public static function setUpBeforeClass(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            self::markTestSkipped('Database connection not available');
        }
    }

    protected function setUp(): void {
        $this->db = new ParlDB();

        // Verify connection exists
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test get_person_id with empty constituency returns FALSE.
     */
    public function test_get_person_id_empty(): void {
        $result = $this->get_person_id('');
        $this->assertFalse($result);
    }

    /**
     * Test get_person_id with invalid constituency returns FALSE.
     */
    public function test_get_person_id_invalid(): void {
        $result = $this->get_person_id('Invalid Constituency XYZ');
        $this->assertFalse($result);
    }

    /**
     * Test get_person_id query structure.
     */
    public function test_get_person_id_query(): void {
        // Test that a query against member table structure works
        $q = $this->db->query('SELECT person_id FROM member WHERE left_reason = ? AND house = ? LIMIT 1', 'still_in_office', 1);
        // Query should succeed
        $this->assertIsObject($q);
    }

    /**
     * Helper function to replicate get_person_id logic.
     */
    private function get_person_id($c) {
        if ($c == '') {
            return FALSE;
        }
        if ($c == 'Orkney ') {
            $c = 'Orkney &amp; Shetland';
        }
        $n = normalise_constituency_name($c);
        if ($n) {
            $c = $n;
        }
        $q = $this->db->query("SELECT person_id FROM member
            WHERE constituency = ?
            AND left_reason = 'still_in_office' AND house=1", $c);
        if ($q->rows > 0) {
            return $q->field(0, 'person_id');
        }
        return FALSE;
    }

}
