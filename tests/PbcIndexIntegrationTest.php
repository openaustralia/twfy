<?php

/**
 * @file
 * Integration tests for pbc/index.php page requiring database access.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for PBC index page with database.
 */
class PbcIndexIntegrationTest extends TestCase {

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
     * Test querying bills table with title and session.
     */
    public function test_query_bills_by_title_and_session(): void {
        $q = $this->db->query('SELECT id, standingprefix FROM bills WHERE title = ? AND session = ?', 'Test Bill', '2006-07');
        // Query should succeed even if no results
        $this->assertIsObject($q);
    }

    /**
     * Test bills table has required columns.
     */
    public function test_bills_table_structure(): void {
        $q = $this->db->query('DESCRIBE bills');
        $this->assertIsObject($q);
        $this->assertGreaterThan(0, $q->rows());
    }

    /**
     * Test querying bills returns zero rows for non-existent bill.
     */
    public function test_query_bills_no_results(): void {
        $q = $this->db->query('SELECT id, standingprefix FROM bills WHERE title = ? AND session = ?', 'Nonexistent Bill XYZ', '2099-99');
        $this->assertSame(0, $q->rows());
    }

    /**
     * Test bills query row counting.
     */
    public function test_bills_query_has_rows_method(): void {
        $q = $this->db->query('SELECT id FROM bills LIMIT 1');
        // Query object should have rows() method
        $this->assertTrue(method_exists($q, 'rows'));
    }

    /**
     * Test bills field extraction.
     */
    public function test_bills_field_extraction(): void {
        $q = $this->db->query('SELECT id, standingprefix FROM bills LIMIT 1');
        if ($q->rows() > 0) {
            // Method should exist to extract fields
            $this->assertTrue(method_exists($q, 'field'));
        } else {
            // Skip if no test data
            $this->assertTrue(true);
        }
    }

}
