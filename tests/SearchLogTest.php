<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../www/includes/easyparliament/searchlog.php';

/**
 * Tests for SEARCHLOG deduplication of search terms.
 */
class SearchLogTest extends TestCase {

    /**
     * Test that add() deduplicates repeated terms before storing.
     */
    public function test_add_deduplicates_repeated_speaker_terms(): void {
        $stored_query = null;

        $db = $this->createMock(ParlDB::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturnCallback(function () use (&$stored_query) {
                // The second argument (index 1) is the query_string value.
                $args = func_get_args();
                $stored_query = $args[1];
                return $this->createMock(MySQLQuery::class);
            });

        $searchlog = new SEARCHLOG();
        // Replace the private db property via reflection.
        $ref = new ReflectionClass($searchlog);
        $prop = $ref->getProperty('db');
        $prop->setValue($searchlog, $db);

        $searchlog->add([
            'query' => 'speaker:10749 speaker:10749 speaker:10749',
            'page' => 1,
            'hits' => 5,
        ]);

        $this->assertSame('speaker:10749', $stored_query);
    }

    /**
     * Test that add() preserves distinct terms.
     */
    public function test_add_preserves_distinct_terms(): void {
        $stored_query = null;

        $db = $this->createMock(ParlDB::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturnCallback(function () use (&$stored_query) {
                $args = func_get_args();
                $stored_query = $args[1];
                return $this->createMock(MySQLQuery::class);
            });

        $searchlog = new SEARCHLOG();
        $ref = new ReflectionClass($searchlog);
        $prop = $ref->getProperty('db');
        $prop->setValue($searchlog, $db);

        $searchlog->add([
            'query' => 'climate change speaker:10749',
            'page' => 1,
            'hits' => 10,
        ]);

        $this->assertSame('climate change speaker:10749', $stored_query);
    }

    /**
     * Test that add() deduplicates mixed repeated terms.
     */
    public function test_add_deduplicates_mixed_repeated_terms(): void {
        $stored_query = null;

        $db = $this->createMock(ParlDB::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturnCallback(function () use (&$stored_query) {
                $args = func_get_args();
                $stored_query = $args[1];
                return $this->createMock(MySQLQuery::class);
            });

        $searchlog = new SEARCHLOG();
        $ref = new ReflectionClass($searchlog);
        $prop = $ref->getProperty('db');
        $prop->setValue($searchlog, $db);

        $searchlog->add([
            'query' => 'budget budget speaker:10749 speaker:10749',
            'page' => 1,
            'hits' => 3,
        ]);

        $this->assertSame('budget speaker:10749', $stored_query);
    }

    /**
     * Test that popular_recent returns deduplicated URLs.
     */
    public function test_popular_recent_deduplicates_query_in_url(): void {
        // Create a mock query result that returns a duplicated query_string.
        $mockResult = $this->createMock(MySQLQuery::class);
        $mockResult->method('rows')->willReturn(1);
        $mockResult->method('field')->willReturnCallback(function ($row, $field) {
            if ($field === 'query_string') {
                return 'speaker:10749 speaker:10749 speaker:10749';
            }
            return null;
        });
        $mockResult->method('row')->willReturn([
            'query_string' => 'speaker:10749 speaker:10749 speaker:10749',
            'c' => 34,
        ]);

        $db = $this->createMock(ParlDB::class);
        $db->method('query')->willReturn($mockResult);

        $searchlog = new SEARCHLOG();
        $ref = new ReflectionClass($searchlog);
        $prop = $ref->getProperty('db');
        $prop->setValue($searchlog, $db);

        $results = $searchlog->popular_recent(10);

        $this->assertCount(1, $results);
        // The URL should contain speaker:10749 only once.
        $url = $results[0]['url'];
        $this->assertSame(1, substr_count($url, 'speaker'));
        $this->assertStringContainsString('speaker%3A10749', $url);
        $this->assertStringContainsString('pop=1', $url);
    }
}
