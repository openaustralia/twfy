<?php

/**
 * @file
 * The admin usage page's tables. Uses a documentation IP address and a
 * made-up request.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\UsageStats\UsagePage;

uses(TransactionalTestCase::class);

describe('UsagePage::tables()', function () {
    it('has the four tables', function () {
        expect(array_keys(UsagePage::tables('1999-01-01', '1999-01-31', [])))->toBe([
            'Busiest API keys',
            'Data site downloads per day',
            'Most downloaded files',
            'Busiest visitors',
        ]);
    });

    describe('without any usage', function () {
        it('has no rows', function () {
            $rows = array_map(fn (array $table) => $table[1], UsagePage::tables('1999-01-01', '1999-01-31', []));

            expect(array_filter($rows))->toBe([]);
        });
    });

    describe('with a download', function () {
        it('lists its file', function () {
            DataDownload::create([
                'request_time' => '1999-01-10 12:00:00',
                'ip_address' => '203.0.113.5',
                'method' => 'GET',
                'path' => '/usage-page-spec.xml',
                'status' => 200,
                'bytes' => 512,
                'referrer' => '',
                'user_agent' => 'ExampleBot/1.0',
                'line_hash' => sha1(random_bytes(16)),
            ]);

            expect(UsagePage::tables('1999-01-01', '1999-01-31', [])['Most downloaded files'][1])
              ->toBe([['/usage-page-spec.xml', '1']]);
        });
    });
});
