<?php

namespace OpenAustralia\TWFY\UsageStats;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The two Elasticsearch 7.x calls the usage stats export needs, over Guzzle.
 *
 * The Guzzle client carries the cluster's address (base_uri) and API key.
 */
class ElasticsearchClient {

    private ClientInterface $http;

    /**
     * @var array<string, true>
     */
    private array $created = [];

    public function __construct(ClientInterface $http) {
        $this->http = $http;
    }

    /**
     * Creates an index with fixed field types, once per run, if it doesn't exist.
     *
     * @param string $index
     *   The index name.
     * @param array<string, mixed> $mappings
     *   Its field types.
     */
    public function ensureIndex(string $index, array $mappings): void {
        if (isset($this->created[$index])) {
            return;
        }
        try {
            $this->http->request('PUT', rawurlencode($index), ['json' => ['mappings' => $mappings]]);
        } catch (ClientException $e) {
            if (!str_contains((string) $e->getResponse()->getBody(), 'resource_already_exists_exception')) {
                throw $e;
            }
        }
        $this->created[$index] = true;
    }

    /**
     * Sends documents in one _bulk request, each stored under its own id.
     *
     * @param array<int, array{index: string, id: string, body: array<string, mixed>}> $documents
     *   Each document with its index and id.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *   Resolves to the reasons for any documents Elasticsearch rejected, or
     *   rejects if the request itself failed.
     */
    public function bulkAsync(array $documents): PromiseInterface {
        $body = '';
        foreach ($documents as $document) {
            $body .= json_encode(['index' => ['_index' => $document['index'], '_id' => $document['id']]], JSON_THROW_ON_ERROR) . "\n";
            $body .= json_encode($document['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
        }
        return $this->http->requestAsync('POST', '_bulk', [
            'headers' => ['Content-Type' => 'application/x-ndjson'],
            'body' => $body,
        ])->then(fn (ResponseInterface $response) => self::rejections($response));
    }

    /**
     * @return string[]
     *   The id and error type of each document a _bulk response rejected.
     *   Not the reason, which can quote the field's value.
     */
    private static function rejections(ResponseInterface $response): array {
        $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (empty($result['errors'])) {
            return [];
        }
        $reasons = [];
        foreach ($result['items'] as $item) {
            $error = $item['index']['error'] ?? null;
            if ($error !== null) {
                $reasons[] = $item['index']['_id'] . ': ' . ($error['type'] ?? 'unknown');
            }
        }
        return $reasons;
    }

}
