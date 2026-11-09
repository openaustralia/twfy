<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

use OpenAustralia\TWFY\Models\ApiStat;

/**
 * Logging each API call. Reads back through Eloquent, the connection the
 * logging writes on. Uses fictional keys and documentation IP addresses.
 */
class ApiLogCallIntegrationTest extends TransactionalTestCase {

    private const KEY = 'testkey1031aaaaaaaaaaaa';

    /**
     * $_SERVER values before the test, restored afterwards.
     *
     * @var array
     */
    private $savedServer = [];

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        foreach (['REMOTE_ADDR', 'REQUEST_URI'] as $name) {
            $this->savedServer[$name] = $_SERVER[$name] ?? null;
        }
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
    }

    /**
     *
     */
    protected function tearDown(): void {
        foreach ($this->savedServer as $name => $value) {
            if ($value === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $value;
            }
        }
        parent::tearDown();
    }

    /**
     *
     */
    public function test_logs_the_call_with_the_key_stripped_from_the_query(): void {
        $_SERVER['REQUEST_URI'] = '/api/getDebates?key=' . self::KEY . '&type=senate&date=2026-11-09';
        api_log_call(self::KEY);
        $row = ApiStat::where('api_key', self::KEY)->sole();
        $this->assertSame('192.0.2.10', $row->ip_address);
        $this->assertSame('/api/getDebates?type=senate&date=2026-11-09', $row->query);
        $this->assertEqualsWithDelta(time(), strtotime($row->query_time), 5);
    }

    /**
     *
     */
    public function test_stores_non_ascii_query_text_unchanged(): void {
        $_SERVER['REQUEST_URI'] = '/api/getDebates?key=' . self::KEY . '&search=Māori%20café';
        api_log_call(self::KEY);
        $this->assertSame('/api/getDebates?search=Māori%20café', ApiStat::where('api_key', self::KEY)->sole()->query);
    }

    /**
     *
     */
    public function test_docs_explorer_calls_are_not_logged(): void {
        $_SERVER['REQUEST_URI'] = '/api/getDebates?key=DOCS';
        api_log_call('DOCS');
        $this->assertSame(0, ApiStat::where('api_key', 'DOCS')->count());
    }

}
