<?php

namespace OpenAustralia\TWFY\UsageStats;

use GuzzleHttp\Promise\Utils;
use OpenAustralia\TWFY\Models\ExportCursor;

/**
 * Sends a table's new rows to Elasticsearch, carrying on from the last id sent.
 *
 * Each document's id is its row id, and its index depends only on the row's
 * own year, so sending a row again overwrites it rather than counting it
 * twice. Yearly indices keep the shard count low: a year of API calls is a
 * few hundred MB.
 */
class Exporter {

    private ElasticsearchClient $es;

    private int $batchSize;

    private int $concurrency;

    /**
     * @param ElasticsearchClient $es
     *   Where to send.
     * @param int $batchSize
     *   Rows per bulk request.
     * @param int $concurrency
     *   Bulk requests in flight at once.
     */
    public function __construct(ElasticsearchClient $es, int $batchSize = 5000, int $concurrency = 1) {
        $this->es = $es;
        $this->batchSize = max(1, $batchSize);
        $this->concurrency = max(1, $concurrency);
    }

    /**
     * The index for a document from the given UTC time.
     */
    public static function indexName(string $prefix, string $time): string {
        return $prefix . '-' . substr($time, 0, 4);
    }

    /**
     * Sends every row after the source's last-sent id.
     *
     * Stops at the first batch with a failure. Batches before it are recorded
     * as sent; it and any sent alongside it go again next run.
     *
     * @return array<string, mixed>
     *   Rows sent, the last id now recorded, and why the failed batch failed.
     */
    public function export(ExportSource $source): array {
        $name = $source->cursorName();
        $lastId = ExportCursor::lastId($name);
        $sent = 0;

        while (true) {
            $batches = $this->nextBatches($source, $lastId);
            if (!$batches) {
                break;
            }

            $promises = array_map(fn (array $batch) => $this->es->bulkAsync($this->prepare($source, $batch)), $batches);
            foreach (Utils::settle($promises)->wait() as $i => $result) {
                $rejected = $result['state'] === 'fulfilled' ? $result['value'] : [$result['reason']->getMessage()];
                if ($rejected) {
                    ExportCursor::advance($name, $lastId);
                    return ['sent' => $sent, 'last_id' => $lastId, 'rejected' => $rejected];
                }
                $lastId = end($batches[$i])['id'];
                $sent += count($batches[$i]);
            }
            ExportCursor::advance($name, $lastId);

            if (count(end($batches)) < $this->batchSize) {
                break;
            }
        }
        return ['sent' => $sent, 'last_id' => $lastId, 'rejected' => []];
    }

    /**
     * Up to one batch per request in flight, stopping at the end of the table.
     *
     * @return array<int, array<int, array<string, mixed>>>
     *   Batches of documents, as ExportSource::documentsAfter() returns them.
     */
    private function nextBatches(ExportSource $source, int $afterId): array {
        $batches = [];
        while (count($batches) < $this->concurrency) {
            $batch = $source->documentsAfter($afterId, $this->batchSize);
            if (!$batch) {
                break;
            }
            $batches[] = $batch;
            $afterId = end($batch)['id'];
            if (count($batch) < $this->batchSize) {
                break;
            }
        }
        return $batches;
    }

    /**
     * Places each document in its index, creating the index if needed.
     *
     * @param ExportSource $source
     *   The source the batch came from.
     * @param array<int, array<string, mixed>> $batch
     *   Documents as ExportSource::documentsAfter() returns them.
     *
     * @return array<int, array<string, mixed>>
     *   Documents as ElasticsearchClient::bulkAsync() takes them.
     */
    private function prepare(ExportSource $source, array $batch): array {
        return array_map(function (array $document) use ($source) {
            $index = self::indexName($source->indexPrefix(), $document['time']);
            $this->es->ensureIndex($index, $source->mappings());
            return ['index' => $index, 'id' => (string) $document['id'], 'body' => $document['body']];
        }, $batch);
    }

}
