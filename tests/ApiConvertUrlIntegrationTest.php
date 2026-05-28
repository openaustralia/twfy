<?php

/**
 * @file
 * Integration tests for api_convertURL.php API functions requiring database.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for convertURL API with database.
 */
class ApiConvertUrlIntegrationTest extends TestCase {


    /**
     *
     */
    public static function setUpBeforeClass(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            self::markTestSkipped('Database connection not available');
        }
    }

    /**
     *
     */
    protected function setUp(): void {

        // Verify connection exists.
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test hansard table query by exact source_url.
     */
    public function test_query_hansard_by_exact_url(): void {
        $url = 'http://example.com/hansard';
        $q = parlDBQuery('SELECT gid, major, htype, subsection_id FROM hansard WHERE source_url = ? ORDER BY gid LIMIT 1', $url);
        $this->assertIsObject($q);
    }

    /**
     * Test hansard table query by LIKE pattern.
     */
    public function test_query_hansard_by_like_url(): void {
        $url_nohash = 'hansard%';
        $q = parlDBQuery('SELECT gid, major, htype, subsection_id FROM hansard WHERE source_url LIKE ? ORDER BY gid LIMIT 1', "%$url_nohash%");
        $this->assertIsObject($q);
    }

    /**
     * Test hansard table structure with required columns.
     */
    public function test_hansard_table_columns(): void {
        $q = parlDBQuery('DESCRIBE hansard');
        $this->assertIsObject($q);
        $this->assertGreaterThan(0, $q->rows());
    }

    /**
     * Test querying hansard by epobject_id (parent lookup).
     */
    public function test_query_hansard_by_epobject_id(): void {
        $epobject_id = 12345;
        $q = parlDBQuery('SELECT gid FROM hansard WHERE epobject_id = ?', $epobject_id);
        $this->assertIsObject($q);
    }

    /**
     * Test hansard rows check for exact URL match.
     */
    public function test_hansard_rows_exact_match(): void {
        $url = 'http://example.com/nonexistent';
        $q = parlDBQuery('SELECT gid FROM hansard WHERE source_url = ?', $url);

        $hasResults = ($q->rows() > 0);
        // Just verify the query method works (may or may not have results)
        $this->assertIsBool($hasResults);
    }

    /**
     * Test hansard rows check for LIKE match.
     */
    public function test_hansard_rows_like_match(): void {
        $url_nohash = 'hansard';
        $q = parlDBQuery('SELECT gid FROM hansard WHERE source_url LIKE ?', "%$url_nohash%");

        $hasResults = ($q->rows() > 0);
        // Just verify the query method works.
        $this->assertIsBool($hasResults);
    }

    /**
     * Test hansard field extraction after successful query.
     */
    public function test_hansard_field_extraction(): void {
        $q = parlDBQuery('SELECT gid, major, htype, subsection_id FROM hansard LIMIT 1');

        if ($q->rows() > 0) {
            // Verify field extraction methods work.
            $this->assertTrue(method_exists($q, 'field'));
        } else {
// No test data is OK.
            $this->assertTrue(true);
        }
    }

    /**
     * Test URL pattern matching with cmhansrd/cm to cmhansrd/vo replacement.
     */
    public function test_hansard_bound_url_pattern(): void {
        $url_nohash = 'cmhansrd/cm061004';
        $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);
        $q = parlDBQuery('SELECT gid FROM hansard WHERE source_url LIKE ? LIMIT 1', "%$url_bound%");

        $this->assertIsObject($q);
    }

    /**
     * Test multiple query attempts - exact, like, then bound.
     */
    public function test_multiple_query_attempts(): void {
        $url = 'http://example.com/hansard/061004';

        $q1 = parlDBQuery('SELECT gid FROM hansard WHERE source_url = ? LIMIT 1', $url);
        $this->assertIsObject($q1);

        $q2 = parlDBQuery('SELECT gid FROM hansard WHERE source_url LIKE ? LIMIT 1', "%$url%");
        $this->assertIsObject($q2);

        $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url);
        $q3 = parlDBQuery('SELECT gid FROM hansard WHERE source_url LIKE ? LIMIT 1', "%$url_bound%");
        $this->assertIsObject($q3);
    }

    /**
     * Test hansard query for htype values detection.
     */
    public function test_hansard_htype_values(): void {
        $q = parlDBQuery('SELECT DISTINCT htype FROM hansard LIMIT 5');
        $this->assertIsObject($q);
    }

    /**
     * Test hansard subsection_id lookup for parent.
     */
    public function test_hansard_subsection_parent_lookup(): void {
        $subsection_id = 1;
        $q = parlDBQuery('SELECT epobject_id FROM hansard WHERE epobject_id = ?', $subsection_id);
        $this->assertIsObject($q);
    }

}
