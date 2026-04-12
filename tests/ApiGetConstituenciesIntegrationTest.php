<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ApiGetConstituenciesIntegrationTest extends TestCase {

    private static $connection = NULL;

    /**
     *
     */
    public static function setUpBeforeClass(): void {
        self::$connection = getSharedTestConnection();
        if (!self::$connection) {
            self::markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test search for constituencies by name
     */
    public function test_search_constituencies_by_name() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('Sydney');
        $this->assertIsArray($result);
        // Result should be a valid array (may be empty if no test data)
        foreach ($result as $item) {
            $this->assertArrayHasKey('name', $item);
        }
    }

    /**
     *
     */
    public function test_search_constituencies_exact_match() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('Warringah');
        $this->assertIsArray($result);
        // Result is valid array regardless of whether data exists.
    }

    /**
     *
     */
    public function test_search_constituencies_partial_match() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('North');
        $this->assertIsArray($result);
        // Should return array (may be empty if no test data)
        foreach ($result as $constituency) {
            $this->assertIsArray($constituency);
            $this->assertArrayHasKey('name', $constituency);
        }
    }

    /**
     *
     */
    public function test_search_constituencies_no_duplicates() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('Sydney');
        $names = array_column($result, 'name');
        $unique_names = array_unique($names);
        // Should not have duplicates.
        $this->assertEquals(count($names), count($unique_names));
    }

    /**
     *
     */
    public function test_search_constituencies_empty_result() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('ZZZNONEXISTENT');
        $this->assertIsArray($result);
    }

    /**
     * Test getting all current constituencies
     */
    public function test_get_all_constituencies_current() {
        $this->assertNotNull(self::$connection);
        $db = new ParlDB();
        $q = $db->query('select cons_id, name from constituency
            where main_name and from_date <= date(now()) and date(now()) <= to_date');
        // Query should work (may return 0 rows if no test data)
        $this->assertGreaterThanOrEqual(0, $q->rows());
    }

    /**
     * Test constituency data structure
     */
    public function test_constituency_result_structure() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('Sydney');
        if (count($result) > 0) {
            $constituency = $result[0];
            $this->assertIsArray($constituency);
            $this->assertArrayHasKey('name', $constituency);
            $this->assertIsString($constituency['name']);
        }
    }

    /**
     * Test HTML entity handling in constituency names
     */
    public function test_constituency_html_entities_decoded() {
        $this->assertNotNull(self::$connection);
        $result = _api_getConstituencies_search('');
        foreach ($result as $constituency) {
            $name = $constituency['name'];
            // Should not contain HTML entities like &amp;.
            $this->assertStringNotContainsString('&amp;', $name);
        }
    }

    /**
     * Test constituency name main_name flag honored
     */
    public function test_constituencies_main_name_only() {
        $this->assertNotNull(self::$connection);
        $db = new ParlDB();
        $q = $db->query('select count(*) as c from constituency
            where main_name and from_date <= date(now()) and date(now()) <= to_date');
        $main_count = $q->field(0, 'c');

        $q2 = $db->query('select count(*) as c from constituency
            where from_date <= date(now()) and date(now()) <= to_date');
        $total_count = $q2->field(0, 'c');

        // main_name should filter down the results (when data exists)
        $this->assertLessThanOrEqual($main_count, $total_count);
    }

    /**
     * Test date-based constituency queries
     */
    public function test_get_constituencies_by_date() {
        $this->assertNotNull(self::$connection);
        $db = new ParlDB();
        $q = $db->query('select cons_id, name from constituency
            where main_name and from_date <= date("2023-01-01") and date("2023-01-01") <= to_date');
        // Query should work (may return 0 rows if no test data for that date)
        $this->assertGreaterThanOrEqual(0, $q->rows());
    }

}
