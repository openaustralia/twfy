<?php

/**
 * @file
 * Building Elasticsearch documents from api_stats rows. Uses fictional keys
 * and documentation IP addresses.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\Models\ApiStat;
use OpenAustralia\TWFY\UsageStats\ApiCallSource;

/**
 * The document for a call with the given stored query.
 */
function api_call_source_spec_body(string $query, ?int $keyId = 7): array {
    $source = new ApiCallSource([
        'getDebates' => ['type', 'date', 'search'],
        'getSenator' => ['id'],
    ], 'test');
    $row = new ApiStat([
        'api_key' => 'fictionalkeyaaaaaaaaaaaa',
        'ip_address' => '192.0.2.10',
        'query_time' => '2026-11-21 22:58:48',
        'query' => $query,
    ]);
    return $source->body($row, $keyId);
}

describe('ApiCallSource->body()', function () {
    describe('with a call to a known method', function () {
        beforeEach(function () {
            $this->body = api_call_source_spec_body('/api/getdebates?type=senate&date=2026-11-20&output=xml&nope=1');
        });

        it('names the site', function () {
            expect($this->body['site'])->toBe('openaustralia');
        });

        it('holds the key id', function () {
            expect($this->body['user'])->toBe(['id' => 7]);
        });

        it('names the method as the API does', function () {
            expect($this->body['endpoint'])->toBe('getDebates');
        });

        it('keeps the parameters it reads', function () {
            expect((array) $this->body['params'])->toBe(['type' => 'senate', 'date' => '2026-11-20', 'output' => 'xml']);
        });

        it('keeps the query as stored', function () {
            expect($this->body['query'])->toBe('/api/getdebates?type=senate&date=2026-11-20&output=xml&nope=1');
        });

        it('keeps the IP address', function () {
            expect($this->body['ip_address'])->toBe('192.0.2.10');
        });

        it('gives the time in UTC', function () {
            expect($this->body['query_time'])->toBe('2026-11-21T22:58:48Z');
        });
    });

    it('reads the method from a parameter', function () {
        expect(api_call_source_spec_body('/api/index.php?method=getSenator&id=1')['endpoint'])->toBe('getSenator');
    });

    it('keeps an unknown method as sent', function () {
        expect(api_call_source_spec_body('/api/getNothing')['endpoint'])->toBe('getNothing');
    });

    it('sends params as an object', function () {
        expect(json_encode(api_call_source_spec_body('/api/getSenator')))->toContain('"params":{}');
    });

    it('sends an array value as text', function () {
        expect(api_call_source_spec_body('/api/getSenator?id[]=1')['params']->id)->toBe('["1"]');
    });

    it('sends no id for an unknown key', function () {
        expect(api_call_source_spec_body('/api/getSenator', null)['user'])->toBe(['id' => null]);
    });

    it('drops invalid UTF-8', function () {
        $body = api_call_source_spec_body("/api/getDebates?search=caf\xe9");

        expect(json_encode($body))->not->toBeFalse();
    });
});

describe('ApiCallSource::withoutKey()', function () {
    it('removes the key', function (string $query, string $expected) {
        expect(ApiCallSource::withoutKey($query))->toBe($expected);
    })->with([
        'first' => ['/api/getSenator?key=abc&id=1', '/api/getSenator?id=1'],
        'last' => ['/api/getSenator?id=1&key=abc', '/api/getSenator?id=1'],
        'only' => ['/api/getSenator?key=abc', '/api/getSenator'],
        'encoded name' => ['/api/getSenator?ke%79=abc&id=1', '/api/getSenator?id=1'],
    ]);

    it('keeps names that only contain key', function () {
        expect(ApiCallSource::withoutKey('/api/getSenator?monkey=1'))->toBe('/api/getSenator?monkey=1');
    });
});
