<?php

/**
 * @file
 * Recounting api_daily_usage from api_stats. Uses fictional keys and
 * documentation IP addresses.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;
use OpenAustralia\TWFY\UsageStats\DailyUsageRebuild;

uses(TransactionalTestCase::class);

/**
 * Logs one call with the given key at the given UTC time.
 */
function rebuild_spec_call(string $key, string $time): void {
    ApiStat::create(['api_key' => $key, 'ip_address' => '192.0.2.10', 'query_time' => $time, 'query' => '/api/getSenator?id=1']);
}

/**
 * The count for a key on a day, or null if there is no row.
 */
function rebuild_spec_count(int $keyId, string $date): ?int {
    return ApiDailyUsage::where('api_key_id', $keyId)->where('date', $date)->value('count');
}

describe('DailyUsageRebuild::run()', function () {
    beforeEach(function () {
        $this->keyId = ApiKey::create([
            'user_id' => 1,
            'api_key' => 'rebuildspeckeyaaaaaaaaaa',
            'commercial' => 0,
            'created' => '2026-01-01 00:00:00',
            'reason' => 'Test',
        ])->id;
        $this->today = gmdate('Y-m-d');
        rebuild_spec_call('rebuildspeckeyaaaaaaaaaa', '2009-05-25 21:12:39');
        rebuild_spec_call('rebuildspeckeyaaaaaaaaaa', '2009-05-25 23:59:59');
        rebuild_spec_call('REBUILDSPECKEYAAAAAAAAAA', '2009-05-26 00:00:00');
        rebuild_spec_call('rebuildspecunknownaaaaaa', '2009-05-25 12:00:00');
        rebuild_spec_call('rebuildspeckeyaaaaaaaaaa', "{$this->today} 00:00:01");
        ApiDailyUsage::insert([
            ['api_key_id' => $this->keyId, 'date' => '2009-05-25', 'count' => 99],
            ['api_key_id' => $this->keyId, 'date' => $this->today, 'count' => 5],
        ]);
        DailyUsageRebuild::run(2);
    });

    it('counts calls per UTC day', function () {
        expect(rebuild_spec_count($this->keyId, '2009-05-25'))->toBe(2);
    });

    it('matches keys whatever their case', function () {
        expect(rebuild_spec_count($this->keyId, '2009-05-26'))->toBe(1);
    });

    it('leaves today to the live counter', function () {
        expect(rebuild_spec_count($this->keyId, $this->today))->toBe(5);
    });

    it('counts nothing for unknown keys', function () {
        expect(ApiDailyUsage::whereNotIn('api_key_id', ApiKey::pluck('id'))->count())->toBe(0);
    });
});
