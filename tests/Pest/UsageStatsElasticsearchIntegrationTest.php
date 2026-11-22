<?php

/**
 * @file
 * Sending usage stats to a real Elasticsearch 7.17, as production's is.
 *
 * Needs ELASTICSEARCH_TEST_URL, which `make test-all` and CI set. Skips
 * without it, except in CI, where it fails. Each test writes to its own
 * indices and deletes them afterwards. Uses fictional keys, documentation IP
 * addresses and made-up requests.
 */

require_once __DIR__ . '/../bootstrap.php';

use GuzzleHttp\Client;
use OpenAustralia\TWFY\Models\ApiStat;
use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\Models\ExportCursor;
use OpenAustralia\TWFY\UsageStats\ApiCallSource;
use OpenAustralia\TWFY\UsageStats\DownloadSource;
use OpenAustralia\TWFY\UsageStats\ElasticsearchClient;
use OpenAustralia\TWFY\UsageStats\Exporter;

uses(TransactionalTestCase::class);

/**
 * Sends a GET to Elasticsearch and decodes the answer.
 */
function es_spec_get(Client $http, string $path): array {
    return json_decode((string) $http->get($path)->getBody(), true);
}

beforeEach(function () {
    $url = getenv('ELASTICSEARCH_TEST_URL');
    if (!$url) {
        if (getenv('CI')) {
            throw new RuntimeException('ELASTICSEARCH_TEST_URL is not set');
        }
        $this->markTestSkipped('ELASTICSEARCH_TEST_URL is not set; `make services-up` then `make test-all` sets it');
    }
    $this->http = new Client(['base_uri' => rtrim($url, '/') . '/', 'timeout' => 30]);
    $this->environment = 'spec' . bin2hex(random_bytes(4));
    $this->export = function ($source) {
        $now = new DateTimeImmutable('2026-11-22 00:00:00', new DateTimeZone('UTC'));
        $result = (new Exporter(new ElasticsearchClient($this->http), 2, 2, $now))->export($source);
        $this->http->post('oa-*-' . $this->environment . '-*/_refresh');
        return $result;
    };
    $this->count = fn (string $kind) => es_spec_get($this->http, "oa-$kind-{$this->environment}-*/_count")['count'];

    foreach (['2025-06-30 12:00:00', '2026-11-20 10:00:00', '2026-11-20 11:00:00'] as $time) {
        DataDownload::create([
            'request_time' => $time,
            'ip_address' => '2001:db8::1',
            'method' => 'GET',
            'path' => '/scrapedxml/senate_debates/2026-11-20.xml',
            'status' => 200,
            'bytes' => 51234,
            'referrer' => '',
            'user_agent' => 'ExampleBot/1.0',
            'line_hash' => sha1(random_bytes(16)),
        ]);
    }
    $this->downloads = new DownloadSource($this->environment, []);
});

afterEach(function () {
    if (isset($this->http)) {
        $this->http->delete('oa-*-' . $this->environment . '-*', ['http_errors' => false]);
    }
});

describe('Exporter->export() to Elasticsearch', function () {
    describe('with downloads', function () {
        beforeEach(function () {
            $this->result = ($this->export)($this->downloads);
        });

        it('stores every row', function () {
            expect(($this->count)('data'))->toBe(3);
        });

        it('has nothing rejected', function () {
            expect($this->result['rejected'])->toBe([]);
        });

        it('creates yearly and monthly indices', function () {
            $indices = array_column(es_spec_get($this->http, "_cat/indices/oa-data-{$this->environment}-*?format=json"), 'index');
            sort($indices);

            expect($indices)->toBe(["oa-data-{$this->environment}-2025", "oa-data-{$this->environment}-2026.11"]);
        });

        it('fixes each field type', function (string $field, string $type) {
            $mapping = es_spec_get($this->http, "oa-data-{$this->environment}-2025/_mapping");
            $properties = $mapping["oa-data-{$this->environment}-2025"]['mappings']['properties'];

            expect($properties[$field]['type'])->toBe($type);
        })->with([
            'time as a date' => ['request_time', 'date'],
            'status as a number' => ['status', 'integer'],
            'bytes as a long' => ['bytes', 'long'],
            'internal as a boolean' => ['internal', 'boolean'],
            'text as a keyword' => ['user_agent', 'keyword'],
        ]);

        describe('when sent again from the start', function () {
            beforeEach(function () {
                ExportCursor::advance($this->downloads->cursorName(), 0);
                ($this->export)($this->downloads);
            });

            it('overwrites rather than duplicates', function () {
                expect(($this->count)('data'))->toBe(3);
            });
        });
    });

    describe('with an API call', function () {
        beforeEach(function () {
            $afterId = (int) ApiStat::max('id');
            ApiStat::create([
                'api_key' => 'esspeckeyaaaaaaaaaaaaaaa',
                'ip_address' => '192.0.2.10',
                'query_time' => '2026-11-21 22:58:48',
                'query' => '/api/getDebates?type=senate&date=2026-11-20',
            ]);
            ExportCursor::advance('elasticsearch:api_stats', $afterId);
            ($this->export)(new ApiCallSource(['getDebates' => ['type', 'date']], $this->environment));
            $this->stored = es_spec_get($this->http, "oa-api-{$this->environment}-*/_search")['hits']['hits'][0]['_source'] ?? [];
        });

        it('stores it', function () {
            expect(($this->count)('api'))->toBe(1);
        });

        it('stores the method', function () {
            expect($this->stored['endpoint'])->toBe('getDebates');
        });

        it('stores the parameters', function () {
            expect($this->stored['params'])->toBe(['type' => 'senate', 'date' => '2026-11-20']);
        });
    });
});
