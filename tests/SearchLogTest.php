<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__ . '/../www/includes/easyparliament/searchlog.php';

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

}
