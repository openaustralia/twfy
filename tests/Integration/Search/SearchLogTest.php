<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once INCLUDESPATH . 'easyparliament/searchlog.php';

/**
 * Integration tests for SEARCHLOG using a real database.
 *
 * Each test runs inside a transaction rolled back in tearDown via
 * TransactionalTestCase, so no data persists between tests.
 */
class SearchLogTest extends TransactionalTestCase {

    /**
     *
     */
protected function setUp(): void {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

    /**
     * Insert a row into search_query_log for test setup.
     */
    private function insertSearchLog(
      string $queryString,
      int $countHits,
      string $ipAddress = '127.0.0.1',
      ?string $queryTime = null,
    ): void {
        parlDBQuery(
            'INSERT INTO search_query_log (query_string, page_number, count_hits, ip_address, query_time) VALUES (?, ?, ?, ?, ?)',
            $queryString,
            1,
            $countHits,
            $ipAddress,
            $queryTime ?? date('Y-m-d H:i:s')
        );
    }

    /**
     * Create a SEARCHLOG with a real DB connection and a stub URL.
     */
    private function createSearchLog(): SEARCHLOG {
        $ref = new ReflectionClass(SEARCHLOG::class);
        $searchlog = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('SEARCHURL');
        $prop->setValue($searchlog, $this->createMockUrl());

        return $searchlog;
    }

    /**
     * Create a lightweight URL stub for SEARCHLOG tests.
     */
    private function createMockUrl() {
        return new class() {
            private $session_vars = [];

            public function reset() {
                $this->session_vars = [];
            }

            public function insert($arr) {
                foreach ($arr as $k => $v) {
                    $this->session_vars[$k] = $v;
                }
            }

            public function generate($encode = 'html', $overrideVars = []) {
                $args = [];
                foreach (array_merge($this->session_vars, $overrideVars) as $k => $v) {
                    if ($v != null) {
                        $args[] = "$k=" . urlencode($v);
                    }
                }
                return '/search/?' . implode('&amp;', $args);
            }

        };
    }

    /**
     * Test that add() deduplicates repeated terms before storing.
     */
    public function test_add_deduplicates_repeated_speaker_terms(): void {
        $searchlog = $this->createSearchLog();

        $searchlog->add([
            'query' => 'speaker:10749 speaker:10749 speaker:10749',
            'page' => 1,
            'hits' => 5,
        ]);

        $q = parlDBQuery('SELECT query_string FROM search_query_log WHERE count_hits = ?', 5);
        $stored = $q->rows() > 0 ? $q->field(0, 'query_string') : null;

        $this->assertSame('speaker:10749', $stored);
    }

    /**
     * Test that add() preserves distinct terms.
     */
    public function test_add_preserves_distinct_terms(): void {
        $searchlog = $this->createSearchLog();

        $searchlog->add([
            'query' => 'climate change speaker:10749',
            'page' => 1,
            'hits' => 10,
        ]);

        $q = parlDBQuery('SELECT query_string FROM search_query_log WHERE count_hits = ?', 10);
        $stored = $q->rows() > 0 ? $q->field(0, 'query_string') : null;

        $this->assertSame('climate change speaker:10749', $stored);
    }

    /**
     * Test that add() deduplicates mixed repeated terms.
     */
    public function test_add_deduplicates_mixed_repeated_terms(): void {
        $searchlog = $this->createSearchLog();

        $searchlog->add([
            'query' => 'budget budget speaker:10749 speaker:10749',
            'page' => 1,
            'hits' => 3,
        ]);

        $q = parlDBQuery('SELECT query_string FROM search_query_log WHERE count_hits = ?', 3);
        $stored = $q->rows() > 0 ? $q->field(0, 'query_string') : null;

        $this->assertSame('budget speaker:10749', $stored);
    }

    /**
     * Test that popular_recent returns deduplicated URLs.
     */
    public function test_popular_recent_deduplicates_query_in_url(): void {
        $this->insertSearchLog('speaker:10749 speaker:10749 speaker:10749', 5);

        $searchlog = $this->createSearchLog();
        $results = $searchlog->popular_recent(10);

        $this->assertCount(1, $results);
        $url = $results[0]['url'];
        $this->assertSame(1, substr_count($url, 'speaker'));
        $this->assertStringContainsString('speaker%3A10749', $url);
        $this->assertStringContainsString('pop=1', $url);
    }

    /**
     * Test that admin_recent_searches returns the most recent rows up to limit.
     */
    public function test_admin_recent_searches_applies_limit_and_maps_rows(): void {
        $this->insertSearchLog('cost of living cost of living', 10, '127.0.0.1', '2026-01-02 12:00:00');
        $this->insertSearchLog('housing', 5, '127.0.0.2', '2026-01-02 11:59:00');

        $searchlog = $this->createSearchLog();
        $results = $searchlog->admin_recent_searches(5);

        $this->assertGreaterThanOrEqual(2, count($results));

        $queries = array_column($results, 'query');
        $this->assertContains('cost of living', $queries);
        $this->assertContains('housing', $queries);

        // The most recent row should appear first.
        $this->assertSame('cost of living', $results[0]['query']);
        $this->assertStringContainsString('s=cost+of+living', $results[0]['url']);
    }

    /**
     * Test that admin_failed_searches returns only zero-hit rows.
     */
    public function test_admin_failed_searches_returns_zero_hit_rows(): void {
        $this->insertSearchLog('xyzzy', 0, '10.0.0.1');
        $this->insertSearchLog('xyzzy', 0, '10.0.0.2');
        $this->insertSearchLog('frobulence', 0, '10.0.0.1');

        // Should not appear.
        $this->insertSearchLog('housing', 3, '10.0.0.1');

        $searchlog = $this->createSearchLog();
        $results = $searchlog->admin_failed_searches();

        $queries = array_column($results, 'query');
        $this->assertContains('xyzzy', $queries);
        $this->assertContains('frobulence', $queries);
        $this->assertNotContains('housing', $queries);

        $xyzzyRow = $results[array_search('xyzzy', $queries)];
        $this->assertSame(2, (int) $xyzzyRow['group_count']);
        $this->assertSame(2, (int) $xyzzyRow['count_ips']);
        $this->assertStringContainsString('s=xyzzy', $xyzzyRow['url']);
    }

    /**
     * Test that admin_popular_searches applies limit and excludes speaker queries.
     */
    public function test_admin_popular_searches_applies_limit_and_excludes_speaker_queries(): void {
        $this->insertSearchLog('climate change', 12);
        $this->insertSearchLog('climate change', 8);
        $this->insertSearchLog('housing affordability', 5);

        // Should not appear.
        $this->insertSearchLog('speaker:10749', 20);

        // Should not appear.
        $this->insertSearchLog('zero hits query', 0);

        $searchlog = $this->createSearchLog();
        $results = $searchlog->admin_popular_searches(10);

        $queries = array_column($results, 'query');
        $this->assertContains('climate change', $queries);
        $this->assertContains('housing affordability', $queries);
        $this->assertNotContains('speaker:10749', $queries);
        $this->assertNotContains('zero hits query', $queries);

        // Climate change has 2 entries so should rank first.
        $this->assertSame('climate change', $results[0]['query']);
    }

}
