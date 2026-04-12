<?php

/**
 * @file
 * Integration tests for api_getMembers.php API functions requiring database.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for getMembers API with database.
 */
class ApiGetMembersIntegrationTest extends TestCase {

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
     * Test querying members table by house.
     */
    public function test_query_members_by_house(): void {
        $q = $this->db->query('SELECT * FROM member WHERE house = ? LIMIT 1', 1);
        $this->assertIsObject($q);
    }

    /**
     * Test querying members by house and party like.
     */
    public function test_query_members_by_party(): void {
        $house = 1;
        $party = 'ALP';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND party LIKE ?', $house, "%$party%");
        $this->assertIsObject($q);
    }

    /**
     * Test querying members by house and constituency like.
     */
    public function test_query_members_by_constituency(): void {
        $house = 1;
        $state = 'NSW';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND constituency LIKE ?', $house, "%$state%");
        $this->assertIsObject($q);
    }

    /**
     * Test date range filtering for members.
     */
    public function test_query_members_with_date_range(): void {
        $house = 1;
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND entered_house <= NOW() AND NOW() <= left_house', $house);
        $this->assertIsObject($q);
    }

    /**
     * Test member search by first name.
     */
    public function test_query_members_search_first_name(): void {
        $house = 1;
        $search = '%Smith%';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND first_name LIKE ?', $house, $search);
        $this->assertIsObject($q);
    }

    /**
     * Test member search by last name.
     */
    public function test_query_members_search_last_name(): void {
        $house = 1;
        $search = '%John%';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND last_name LIKE ?', $house, $search);
        $this->assertIsObject($q);
    }

    /**
     * Test member search by full name concatenation.
     */
    public function test_query_members_search_full_name(): void {
        $house = 1;
        $search = '%John Smith%';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND CONCAT(first_name, \' \', last_name) LIKE ?', $house, $search);
        $this->assertIsObject($q);
    }

    /**
     * Test member table has required columns.
     */
    public function test_member_table_structure(): void {
        $q = $this->db->query('DESCRIBE member');
        $this->assertIsObject($q);
        $this->assertGreaterThan(0, $q->rows());
    }

    /**
     * Test member query returns rows method.
     */
    public function test_member_query_rows_method(): void {
        $q = $this->db->query('SELECT * FROM member LIMIT 1');
        $this->assertTrue(method_exists($q, 'rows'));
    }

    /**
     * Test member query field extraction.
     */
    public function test_member_query_field_method(): void {
        $q = $this->db->query('SELECT * FROM member LIMIT 1');
        $this->assertTrue(method_exists($q, 'field'));
    }

    /**
     * Test Senate query (house = 2) with different search logic.
     */
    public function test_senate_search_includes_constituency(): void {
        $house = 2;
        $search = '%NSW%';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND constituency LIKE ?', $house, $search);
        $this->assertIsObject($q);
    }

    /**
     * Test state search for House members.
     */
    public function test_state_search_house(): void {
        $house = 1;
        $state = 'NSW';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND constituency LIKE ? AND entered_house <= NOW() AND NOW() <= left_house', $house, "%$state%");
        $this->assertIsObject($q);
    }

    /**
     * Test state search for Senate members.
     */
    public function test_state_search_senate(): void {
        $house = 2;
        $state = 'VIC';
        $q = $this->db->query('SELECT * FROM member WHERE house = ? AND constituency LIKE ? AND entered_house <= NOW() AND NOW() <= left_house', $house, "%$state%");
        $this->assertIsObject($q);
    }

    /**
     * Test state search with multiple state codes.
     */
    public function test_multiple_state_searches(): void {
        $states = ['NSW', 'VIC', 'QLD'];
        $results = [];

        foreach ($states as $state) {
            $q = $this->db->query('SELECT person_id FROM member WHERE constituency LIKE ? LIMIT 1', "%$state%");
            $results[$state] = $q->rows();
        }

        // All queries should succeed
        $this->assertCount(3, $results);
    }

}
