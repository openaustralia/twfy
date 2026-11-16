<?php

/**
 * @file
 * Logging each API call. Reads back through Eloquent, the connection the
 * logging writes on. Uses fictional keys and documentation IP addresses.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/docs/api/api_functions.php';

use Illuminate\Database\Eloquent\Builder;
use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;

uses(TransactionalTestCase::class);

const API_LOG_SPEC_KEY = 'testkey1031aaaaaaaaaaaa';
const API_LOG_SPEC_OTHER_KEY = 'testkey1031bbbbbbbbbbbb';

/**
 * Creates a fictional API key and returns its id.
 */
function api_log_spec_create_key(string $key): int {
    return ApiKey::create([
        'user_id' => 1,
        'api_key' => $key,
        'commercial' => 0,
        'created' => '2026-01-01 00:00:00',
        'reason' => 'Test',
    ])->id;
}

/**
 * Logs one call to getDebates with the given key and query.
 */
function api_log_spec_call(string $key, string $query = '&type=senate'): void {
    $_SERVER['REQUEST_URI'] = '/api/getDebates?key=' . $key . $query;
    api_log_call($key);
}

/**
 * The count for a key on a UTC date, or null if there is no row.
 */
function api_log_spec_count(int $apiKeyId, string $date): ?int {
    return ApiDailyUsage::where('api_key_id', $apiKeyId)->where('date', $date)->value('count');
}

/**
 * Seconds between the database clock and the furthest-off row timestamp.
 */
function api_log_spec_timestamp_drift(Builder $rows): int {
    return (int) $rows->selectRaw('MAX(GREATEST(ABS(TIMESTAMPDIFF(SECOND, created_at, NOW())), '
        . 'ABS(TIMESTAMPDIFF(SECOND, updated_at, NOW())))) AS drift')->value('drift');
}

beforeEach(function () {
    $this->savedServer = [];
    foreach (['REMOTE_ADDR', 'REQUEST_URI'] as $name) {
        $this->savedServer[$name] = $_SERVER[$name] ?? null;
    }
    $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
});

afterEach(function () {
    foreach ($this->savedServer as $name => $value) {
        if ($value === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $value;
        }
    }
});

describe('api_log_call()', function () {
    describe('with a call to log', function () {
        beforeEach(function () {
            api_log_spec_call(API_LOG_SPEC_KEY, '&type=senate&date=2026-11-09');
            $this->row = ApiStat::where('api_key', API_LOG_SPEC_KEY)->sole();
        });

        it('records the IP address', function () {
            expect($this->row->ip_address)->toBe('192.0.2.10');
        });

        it('strips the key from the query', function () {
            expect($this->row->query)->toBe('/api/getDebates?type=senate&date=2026-11-09');
        });

        it('records the time of the call', function () {
            expect(abs(time() - strtotime($this->row->query_time)))->toBeLessThanOrEqual(5);
        });
    });

    describe('with non-ASCII query text', function () {
        it('stores it unchanged', function () {
            api_log_spec_call(API_LOG_SPEC_KEY, '&search=Māori%20café');

            expect(ApiStat::where('api_key', API_LOG_SPEC_KEY)->sole()->query)
              ->toBe('/api/getDebates?search=Māori%20café');
        });
    });

    describe('with the docs explorer key', function () {
        beforeEach(function () {
            $this->countsBefore = ApiDailyUsage::count();
            api_log_spec_call('DOCS', '');
        });

        it('logs nothing', function () {
            expect(ApiStat::where('api_key', 'DOCS')->count())->toBe(0);
        });

        it('counts nothing', function () {
            expect(ApiDailyUsage::count())->toBe($this->countsBefore);
        });
    });

    describe('with an unknown key', function () {
        beforeEach(function () {
            $this->countsBefore = ApiDailyUsage::count();
            api_log_spec_call(API_LOG_SPEC_KEY);
        });

        it('logs the call', function () {
            expect(ApiStat::where('api_key', API_LOG_SPEC_KEY)->count())->toBe(1);
        });

        it('counts nothing', function () {
            expect(ApiDailyUsage::count())->toBe($this->countsBefore);
        });
    });

    describe('with a known key', function () {
        beforeEach(function () {
            $this->id = api_log_spec_create_key(API_LOG_SPEC_KEY);
        });

        it('counts each call today', function (int $calls) {
            for ($i = 0; $i < $calls; $i++) {
                api_log_spec_call(API_LOG_SPEC_KEY);
            }

            expect(api_log_spec_count($this->id, gmdate('Y-m-d')))->toBe($calls);
        })->with([1, 3]);

        describe('with a second key', function () {
            beforeEach(function () {
                $this->otherId = api_log_spec_create_key(API_LOG_SPEC_OTHER_KEY);
                api_log_spec_call(API_LOG_SPEC_KEY);
                api_log_spec_call(API_LOG_SPEC_KEY);
                api_log_spec_call(API_LOG_SPEC_OTHER_KEY);
            });

            it('counts the first key alone', function () {
                expect(api_log_spec_count($this->id, gmdate('Y-m-d')))->toBe(2);
            });

            it('counts the second key alone', function () {
                expect(api_log_spec_count($this->otherId, gmdate('Y-m-d')))->toBe(1);
            });
        });

        describe('with a count from yesterday', function () {
            beforeEach(function () {
                $this->yesterday = gmdate('Y-m-d', strtotime('-1 day'));
                ApiDailyUsage::insert(['api_key_id' => $this->id, 'date' => $this->yesterday, 'count' => 5]);
                api_log_spec_call(API_LOG_SPEC_KEY);
            });

            it('leaves yesterday alone', function () {
                expect(api_log_spec_count($this->id, $this->yesterday))->toBe(5);
            });

            it('starts a new row for today', function () {
                expect(api_log_spec_count($this->id, gmdate('Y-m-d')))->toBe(1);
            });
        });

        // The site runs PHP in Sydney time and MySQL in UTC, so the day must
        // come from gmdate() and the row timestamps from the database.
        describe('when PHP runs in Sydney time', function () {
            beforeEach(function () {
                $this->previousZone = date_default_timezone_get();
                date_default_timezone_set('Australia/Sydney');
                api_log_spec_call(API_LOG_SPEC_KEY);
                api_log_spec_call(API_LOG_SPEC_KEY);
            });

            afterEach(function () {
                date_default_timezone_set($this->previousZone);
            });

            it('counts the day in UTC', function () {
                expect(ApiDailyUsage::where('api_key_id', $this->id)->pluck('date')
                  ->map(fn ($date) => $date->format('Y-m-d'))->all())->toBe([gmdate('Y-m-d')]);
            });

            it('stamps logs with database time', function () {
                expect(api_log_spec_timestamp_drift(ApiStat::where('api_key', API_LOG_SPEC_KEY)))
                  ->toBeLessThan(60);
            });

            it('stamps counts with database time', function () {
                expect(api_log_spec_timestamp_drift(ApiDailyUsage::where('api_key_id', $this->id)))
                  ->toBeLessThan(60);
            });
        });
    });
});
