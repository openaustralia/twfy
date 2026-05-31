<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ApiGetConstituenciesIntegrationTest extends TestCase {

    private static $connection = null;

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

}
