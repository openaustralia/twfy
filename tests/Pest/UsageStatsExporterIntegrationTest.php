<?php

/**
 * @file
 * Sending usage stats rows to Elasticsearch, against a fake Elasticsearch.
 * Uses fictional keys, documentation IP addresses and made-up requests.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/FakeElasticsearch.php';

use GuzzleHttp\Psr7\Response;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;
use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\Models\ExportCursor;
use OpenAustralia\TWFY\UsageStats\ApiCallSource;
use OpenAustralia\TWFY\UsageStats\DownloadSource;
use OpenAustralia\TWFY\UsageStats\ElasticsearchClient;
use OpenAustralia\TWFY\UsageStats\Exporter;

uses(TransactionalTestCase::class);

/**
 * Adds a download at the given UTC time and returns its id.
 */
function exporter_spec_download(string $time, string $ip = '203.0.113.5'): int {
    return DataDownload::create([
        'request_time' => $time,
        'ip_address' => $ip,
        'method' => 'GET',
        'path' => '/scrapedxml/',
        'status' => 200,
        'bytes' => 512,
        'referrer' => '',
        'user_agent' => 'ExampleBot/1.0',
        'line_hash' => sha1(random_bytes(16)),
    ])->id;
}

beforeEach(function () {
    $this->es = new FakeElasticsearch();
    $this->source = new DownloadSource('test', ['198.51.100.7']);
    $this->export = fn (int $batchSize = 2, int $concurrency = 1) => (new Exporter(
        new ElasticsearchClient($this->es->client()),
        $batchSize,
        $concurrency,
        new DateTimeImmutable('2026-11-22 00:00:00', new DateTimeZone('UTC'))
    ))->export($this->source);
    $this->ids = [
        exporter_spec_download('2025-12-31 23:00:00'),
        exporter_spec_download('2026-11-20 10:00:00'),
        exporter_spec_download('2026-11-20 11:00:00', '198.51.100.7'),
    ];
});

describe('Exporter::indexName()', function () {
    it('picks the index for a time', function (string $time, string $expected) {
        $now = new DateTimeImmutable('2026-11-22 00:00:00', new DateTimeZone('UTC'));

        expect(Exporter::indexName('oa-api-test', $time, $now))->toBe($expected);
    })->with([
        'this year' => ['2026-01-01 00:00:00', 'oa-api-test-2026.01'],
        'a past year' => ['2009-05-25 21:12:39', 'oa-api-test-2009'],
    ]);
});

describe('Exporter->export()', function () {
    describe('with rows not yet sent', function () {
        beforeEach(function () {
            $this->result = ($this->export)();
        });

        it('sends every row', function () {
            expect(count($this->es->documents()))->toBe(3);
        });

        it('uses row ids as document ids', function () {
            expect(array_column($this->es->documents(), 'id'))->toBe(array_map('strval', $this->ids));
        });

        it('puts each row in its index', function () {
            expect(array_column($this->es->documents(), 'index'))
              ->toBe(['oa-data-test-2025', 'oa-data-test-2026.11', 'oa-data-test-2026.11']);
        });

        it('creates each index once', function () {
            expect($this->es->createdIndices())->toBe(['oa-data-test-2025', 'oa-data-test-2026.11']);
        });

        it('marks OAF servers as internal', function () {
            expect(array_column(array_column($this->es->documents(), 'body'), 'internal'))->toBe([false, false, true]);
        });

        it('records the last id sent', function () {
            expect(ExportCursor::lastId('elasticsearch:data_downloads'))->toBe($this->ids[2]);
        });

        it('reports what it sent', function () {
            expect($this->result)->toBe(['sent' => 3, 'last_id' => $this->ids[2], 'rejected' => []]);
        });

        describe('when run again', function () {
            beforeEach(function () {
                $this->es->requests = [];
                ($this->export)();
            });

            it('sends nothing', function () {
                expect($this->es->documents())->toBe([]);
            });
        });

        describe('with a newer row', function () {
            beforeEach(function () {
                $this->es->requests = [];
                $this->newId = exporter_spec_download('2026-11-21 09:00:00');
                ($this->export)();
            });

            it('sends only the newer row', function () {
                expect(array_column($this->es->documents(), 'id'))->toBe([(string) $this->newId]);
            });
        });
    });

    describe('when a document is rejected', function () {
        beforeEach(function () {
            $rejectId = (string) $this->ids[1];
            $this->es->onBulk = fn (array $documents) => FakeElasticsearch::rejecting($documents, $rejectId);
            $this->result = ($this->export)(1);
        });

        it('keeps the batches before it', function () {
            expect(ExportCursor::lastId('elasticsearch:data_downloads'))->toBe($this->ids[0]);
        });

        it('stops sending', function () {
            expect(count($this->es->documents()))->toBe(2);
        });

        it('says why', function () {
            expect($this->result['rejected'])->toBe(["{$this->ids[1]}: mapper_parsing_exception failed to parse"]);
        });
    });

    describe('when Elasticsearch fails', function () {
        beforeEach(function () {
            $this->es->onBulk = fn () => new Response(503, [], '{}');
            $this->result = ($this->export)();
        });

        it('records nothing as sent', function () {
            expect(ExportCursor::lastId('elasticsearch:data_downloads'))->toBe(0);
        });

        it('reports the failure', function () {
            expect($this->result['rejected'])->toHaveCount(1);
        });
    });

    describe('with requests in parallel', function () {
        beforeEach(function () {
            $rejectId = (string) $this->ids[1];
            $this->es->onBulk = fn (array $documents) => $documents[0]['id'] === $rejectId
                ? FakeElasticsearch::rejecting($documents, $rejectId)
                : null;
            ($this->export)(1, 3);
        });

        it('sends the batches together', function () {
            expect(count($this->es->documents()))->toBe(3);
        });

        it('keeps only batches before a failure', function () {
            expect(ExportCursor::lastId('elasticsearch:data_downloads'))->toBe($this->ids[0]);
        });
    });

    describe('when the index already exists', function () {
        beforeEach(function () {
            $this->es->existingIndices = ['oa-data-test-2025'];
            $this->result = ($this->export)();
        });

        it('sends to it anyway', function () {
            expect($this->result['sent'])->toBe(3);
        });
    });
});

describe('ApiCallSource->documentsAfter()', function () {
    beforeEach(function () {
        $afterId = (int) ApiStat::max('id');
        $keyId = ApiKey::create([
            'user_id' => 1,
            'api_key' => 'exporterspeckeyaaaaaaaaa',
            'commercial' => 0,
            'created' => '2026-01-01 00:00:00',
            'reason' => 'Test',
        ])->id;
        foreach (['exporterspeckeyaaaaaaaaa', 'exporterspecunknownaaaaa', 'EXPORTERSPECKEYAAAAAAAAA'] as $key) {
            ApiStat::create([
                'api_key' => $key,
                'ip_address' => '192.0.2.10',
                'query_time' => '2026-11-21 22:58:48',
                'query' => '/api/getSenator?id=1',
            ]);
        }
        $this->keyId = $keyId;
        $this->documents = (new ApiCallSource(['getSenator' => ['id']], 'test'))->documentsAfter($afterId, 10);
    });

    it('sends the key id for a known key', function () {
        expect($this->documents[0]['body']['user']['id'])->toBe($this->keyId);
    });

    it('sends no id for an unknown key', function () {
        expect($this->documents[1]['body']['user']['id'])->toBeNull();
    });

    it('matches a key whatever its case', function () {
        expect($this->documents[2]['body']['user']['id'])->toBe($this->keyId);
    });

    it('never sends the key itself', function () {
        expect(strtolower(json_encode($this->documents)))->not->toContain('exporterspec');
    });
});
