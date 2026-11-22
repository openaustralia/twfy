<?php

/**
 * @file
 * A Guzzle client answering as Elasticsearch 7 does, recording each request.
 */

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Accepts every index and document unless told otherwise.
 */
class FakeElasticsearch {

    /**
     * @var array<int, array{method: string, path: string, body: string}>
     */
    public array $requests = [];

    /**
     * Given a bulk request's documents, returns a Response or null to accept them all.
     *
     * @var callable|null
     */
    public $onBulk = null;

    /**
     * Indices that already exist, so creating them fails as Elasticsearch's does.
     *
     * @var string[]
     */
    public array $existingIndices = [];

    /**
     * A client whose requests this fake answers.
     */
    public function client(): Client {
        $handler = fn (RequestInterface $request) => $this->handle($request);
        return new Client(['base_uri' => 'http://elasticsearch.test/', 'handler' => HandlerStack::create($handler)]);
    }

    /**
     * Every document sent in a bulk request, in order.
     *
     * @return array<int, array{index: string, id: string, body: array<string, mixed>}>
     *   Each with its index, id and body.
     */
    public function documents(): array {
        $documents = [];
        foreach ($this->requests as $request) {
            if ($request['path'] === '_bulk') {
                array_push($documents, ...self::parseBulk($request['body']));
            }
        }
        return $documents;
    }

    /**
     * The indices created, in order.
     *
     * @return string[]
     *   Index names.
     */
    public function createdIndices(): array {
        return array_values(array_map(
            fn (array $request) => $request['path'],
            array_filter($this->requests, fn (array $request) => $request['method'] === 'PUT')
        ));
    }

    /**
     * Records a request and answers it.
     */
    private function handle(RequestInterface $request): PromiseInterface {
        $path = ltrim($request->getUri()->getPath(), '/');
        $body = (string) $request->getBody();
        $this->requests[] = ['method' => $request->getMethod(), 'path' => $path, 'body' => $body];

        if ($request->getMethod() === 'PUT') {
            return Create::promiseFor(in_array($path, $this->existingIndices, true)
                ? new Response(400, [], '{"error":{"type":"resource_already_exists_exception"},"status":400}')
                : new Response(200, [], '{"acknowledged":true}'));
        }
        $documents = self::parseBulk($body);
        $response = $this->onBulk ? ($this->onBulk)($documents) : null;
        return Create::promiseFor($response ?? self::accepted($documents));
    }

    /**
     * A _bulk response accepting every document.
     *
     * @param array<int, array<string, mixed>> $documents
     *   The documents sent.
     */
    private static function accepted(array $documents): Response {
        $items = array_map(fn (array $document) => ['index' => ['_id' => $document['id'], 'status' => 201]], $documents);
        return new Response(200, [], json_encode(['errors' => false, 'items' => $items]));
    }

    /**
     * A _bulk response rejecting the document with the given id.
     *
     * @param array<int, array<string, mixed>> $documents
     *   The documents sent.
     * @param string $id
     *   The id to reject.
     */
    public static function rejecting(array $documents, string $id): Response {
        $error = ['type' => 'mapper_parsing_exception', 'reason' => 'failed to parse'];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $document['id'] === $id
                ? ['index' => ['_id' => $id, 'status' => 400, 'error' => $error]]
                : ['index' => ['_id' => $document['id'], 'status' => 201]];
        }
        return new Response(200, [], json_encode(['errors' => true, 'items' => $items]));
    }

    /**
     * The documents in a _bulk request body.
     *
     * @return array<int, array{index: string, id: string, body: array<string, mixed>}>
     *   Each with its index, id and body.
     */
    private static function parseBulk(string $body): array {
        $lines = array_values(array_filter(explode("\n", $body), 'strlen'));
        $documents = [];
        for ($i = 0; $i < count($lines); $i += 2) {
            $action = json_decode($lines[$i], true)['index'];
            $documents[] = ['index' => $action['_index'], 'id' => $action['_id'], 'body' => json_decode($lines[$i + 1], true)];
        }
        return $documents;
    }

}
