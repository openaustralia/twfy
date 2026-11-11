<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
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

    /**
     *
     */
    public function test_first_call_of_the_day_creates_a_count_of_one(): void {
        $id = $this->createKey(self::KEY);
        $this->callApi(self::KEY);
        $this->assertSame(1, $this->countFor($id, gmdate('Y-m-d')));
    }

    /**
     *
     */
    public function test_each_later_call_adds_one(): void {
        $id = $this->createKey(self::KEY);
        $this->callApi(self::KEY);
        $this->callApi(self::KEY);
        $this->callApi(self::KEY);
        $this->assertSame(3, $this->countFor($id, gmdate('Y-m-d')));
    }

    /**
     *
     */
    public function test_counts_each_key_separately(): void {
        $first = $this->createKey(self::KEY);
        $second = $this->createKey('testkey1031bbbbbbbbbbbb');
        $this->callApi(self::KEY);
        $this->callApi(self::KEY);
        $this->callApi('testkey1031bbbbbbbbbbbb');
        $this->assertSame(2, $this->countFor($first, gmdate('Y-m-d')));
        $this->assertSame(1, $this->countFor($second, gmdate('Y-m-d')));
    }

    /**
     *
     */
    public function test_a_new_day_starts_a_new_row(): void {
        $id = $this->createKey(self::KEY);
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));
        ApiDailyUsage::insert(['api_key_id' => $id, 'date' => $yesterday, 'count' => 5]);
        $this->callApi(self::KEY);
        $this->assertSame(5, $this->countFor($id, $yesterday));
        $this->assertSame(1, $this->countFor($id, gmdate('Y-m-d')));
    }

    /**
     *
     */
    public function test_day_is_utc_whatever_the_php_time_zone(): void {
        $id = $this->createKey(self::KEY);
        $previousZone = date_default_timezone_get();
        date_default_timezone_set('Australia/Adelaide');
        try {
            $this->callApi(self::KEY);
        } finally {
            date_default_timezone_set($previousZone);
        }
        $this->assertSame([gmdate('Y-m-d')],
            ApiDailyUsage::where('api_key_id', $id)->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->all());
    }

    /**
     *
     */
    public function test_unknown_key_logs_the_call_but_counts_nothing(): void {
        $before = ApiDailyUsage::count();
        $this->callApi(self::KEY);
        $this->assertSame(1, ApiStat::where('api_key', self::KEY)->count());
        $this->assertSame($before, ApiDailyUsage::count());
    }

    /**
     *
     */
    public function test_docs_explorer_calls_are_not_counted(): void {
        $before = ApiDailyUsage::count();
        $_SERVER['REQUEST_URI'] = '/api/getDebates?key=DOCS';
        api_log_call('DOCS');
        $this->assertSame($before, ApiDailyUsage::count());
    }

    /**
     * The site sets PHP to Sydney time while MySQL sessions run in UTC, so
     * the database, not PHP, must fill created_at and updated_at.
     */
    public function test_row_timestamps_match_the_database_clock_whatever_the_php_time_zone(): void {
        $id = $this->createKey(self::KEY);
        $previousZone = date_default_timezone_get();
        date_default_timezone_set('Australia/Sydney');
        try {
            $this->callApi(self::KEY);
            $this->callApi(self::KEY);
        } finally {
            date_default_timezone_set($previousZone);
        }
        $ages = 'ABS(TIMESTAMPDIFF(SECOND, created_at, NOW())) AS created_age, '
            . 'ABS(TIMESTAMPDIFF(SECOND, updated_at, NOW())) AS updated_age';
        foreach ([
            ApiStat::where('api_key', self::KEY)->selectRaw($ages)->first(),
            ApiDailyUsage::where('api_key_id', $id)->selectRaw($ages)->first(),
        ] as $row) {
            $this->assertLessThan(60, $row->created_age);
            $this->assertLessThan(60, $row->updated_age);
        }
    }

    /**
     * Creates a fictional API key and returns its id.
     */
    private function createKey(string $key): int {
        return ApiKey::create([
            'user_id' => 1,
            'api_key' => $key,
            'commercial' => 0,
            'created' => '2026-01-01 00:00:00',
            'reason' => 'Test',
        ])->id;
    }

    /**
     * Logs one call to getDebates with the given key.
     */
    private function callApi(string $key): void {
        $_SERVER['REQUEST_URI'] = '/api/getDebates?key=' . $key . '&type=senate';
        api_log_call($key);
    }

    /**
     * The count for a key on a UTC date, or null if there is no row.
     */
    private function countFor(int $apiKeyId, string $date): ?int {
        return ApiDailyUsage::where('api_key_id', $apiKeyId)->where('date', $date)->value('count');
    }

}
