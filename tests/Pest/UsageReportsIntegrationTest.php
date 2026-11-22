<?php

/**
 * @file
 * The reports on the admin usage page. Uses fictional people, keys,
 * documentation IP addresses and made-up requests.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\UsageStats\ApiUsageReport;
use OpenAustralia\TWFY\UsageStats\DownloadReport;

uses(TransactionalTestCase::class);

/**
 * Creates a fictional person with a key, and returns the key's id.
 */
function usage_reports_spec_key(string $name, string $key): int {
    $user = User::create(['firstname' => $name, 'lastname' => 'Example', 'email' => strtolower($name) . '@example.test']);
    return ApiKey::create([
        'user_id' => $user->user_id,
        'api_key' => $key,
        'commercial' => 0,
        'created' => '2026-01-01 00:00:00',
        'reason' => 'Test',
    ])->id;
}

/**
 * Adds a download at the given UTC time.
 */
function usage_reports_spec_download(string $time, string $path, string $ip = '203.0.113.5'): void {
    DataDownload::create([
        'request_time' => $time,
        'ip_address' => $ip,
        'method' => 'GET',
        'path' => $path,
        'status' => 200,
        'bytes' => 512,
        'referrer' => '',
        'user_agent' => 'ExampleBot/1.0',
        'line_hash' => sha1(random_bytes(16)),
    ]);
}

describe('ApiUsageReport::topKeys()', function () {
    beforeEach(function () {
        $this->steady = usage_reports_spec_key('Steady', 'usagespecsteadyaaaaaaaaa');
        $this->burst = usage_reports_spec_key('Burst', 'usagespecburstaaaaaaaaaa');
        ApiDailyUsage::insert([
            ['api_key_id' => $this->steady, 'date' => '2026-11-01', 'count' => 10],
            ['api_key_id' => $this->steady, 'date' => '2026-11-02', 'count' => 20],
            ['api_key_id' => $this->steady, 'date' => '2026-11-03', 'count' => 30],
            ['api_key_id' => $this->burst, 'date' => '2026-11-02', 'count' => 100],
            ['api_key_id' => $this->burst, 'date' => '2026-10-31', 'count' => 500],
        ]);
        $this->report = ApiUsageReport::topKeys('2026-11-01', '2026-11-03');
        $this->steadyRow = $this->report[1];
    });

    it('puts the busiest key first', function () {
        expect(array_map(fn (array $row) => $row['key']->id, $this->report))->toBe([$this->burst, $this->steady]);
    });

    it('counts only days in the period', function () {
        expect($this->report[0]['total'])->toBe(100);
    });

    it('averages over every day', function () {
        expect($this->report[0]['mean'])->toEqualWithDelta(33.33, 0.01);
    });

    it('counts a day without calls as zero', function () {
        expect($this->report[0]['min'])->toBe(0);
    });

    it('gives the fewest calls in a day', function () {
        expect($this->steadyRow['min'])->toBe(10);
    });

    it('gives the most calls in a day', function () {
        expect($this->steadyRow['max'])->toBe(30);
    });

    it('loads the key holder', function () {
        expect($this->steadyRow['key']->user->firstname)->toBe('Steady');
    });

    it('stops at the limit', function () {
        expect(ApiUsageReport::topKeys('2026-11-01', '2026-11-03', 1))->toHaveCount(1);
    });

    it('skips counts for a deleted key', function () {
        ApiDailyUsage::insert(['api_key_id' => 999999999, 'date' => '2026-11-01', 'count' => 1000]);

        expect(ApiUsageReport::topKeys('2026-11-01', '2026-11-03'))->toHaveCount(2);
    });
});

describe('DownloadReport', function () {
    beforeEach(function () {
        usage_reports_spec_download('2026-10-31 23:59:59', '/old.xml');
        usage_reports_spec_download('2026-11-01 00:00:00', '/a.xml');
        usage_reports_spec_download('2026-11-01 12:00:00', '/a.xml', '198.51.100.7');
        usage_reports_spec_download('2026-11-02 23:59:59', '/b.xml');
        usage_reports_spec_download('2026-11-03 00:00:00', '/new.xml');
        $this->report = new DownloadReport('2026-11-01', '2026-11-02', ['198.51.100.7']);
    });

    describe('->dailyTotals()', function () {
        it('counts each day, newest first', function () {
            expect($this->report->dailyTotals())->toBe([
                ['day' => '2026-11-02', 'downloads' => 1, 'internal' => 0],
                ['day' => '2026-11-01', 'downloads' => 2, 'internal' => 1],
            ]);
        });

        it('counts none as internal without a list', function () {
            $totals = (new DownloadReport('2026-11-01', '2026-11-02', []))->dailyTotals();

            expect(array_column($totals, 'internal'))->toBe([0, 0]);
        });
    });

    describe('->topPaths()', function () {
        it('puts the most downloaded first', function () {
            expect($this->report->topPaths())->toBe([
                ['path' => '/a.xml', 'downloads' => 2],
                ['path' => '/b.xml', 'downloads' => 1],
            ]);
        });
    });

    describe('->topVisitors()', function () {
        it('marks OAF servers', function () {
            $internal = array_column($this->report->topVisitors(), 'internal', 'ip_address');

            expect($internal)->toBe(['203.0.113.5' => false, '198.51.100.7' => true]);
        });
    });
});
