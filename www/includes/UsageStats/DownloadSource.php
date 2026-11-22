<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\DataDownload;

/**
 * data.openaustralia.org.au downloads from data_downloads.
 */
class DownloadSource implements ExportSource {

    private string $environment;

    /**
     * @var string[]
     */
    private array $internalAddresses;

    /**
     * @param string $environment
     *   The stage, such as production, used in index names.
     * @param string[] $internalAddresses
     *   IP addresses of OAF's own servers, such as They Vote for You's.
     */
    public function __construct(string $environment, array $internalAddresses) {
        $this->environment = $environment;
        $this->internalAddresses = $internalAddresses;
    }

    /**
     * {@inheritdoc}
     */
    public function cursorName(): string {
        return 'elasticsearch:data_downloads';
    }

    /**
     * {@inheritdoc}
     */
    public function indexPrefix(): string {
        return "oa-data-{$this->environment}";
    }

    /**
     * {@inheritdoc}
     */
    public function mappings(): array {
        return Mappings::with([
            'request_time' => ['type' => 'date'],
            'status' => ['type' => 'integer'],
            'bytes' => ['type' => 'long'],
            'internal' => ['type' => 'boolean'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function documentsAfter(int $afterId, int $limit): array {
        $rows = DataDownload::where('id', '>', $afterId)->orderBy('id')->limit($limit)->get();
        return $rows->map(fn (DataDownload $row) => [
            'id' => (int) $row->id,
            'time' => $row->request_time,
            'body' => [
                'site' => 'openaustralia-data',
                'request_time' => str_replace(' ', 'T', $row->request_time) . 'Z',
                'ip_address' => $row->ip_address,
                'method' => $row->method,
                'path' => $row->path,
                'status' => $row->status,
                'bytes' => $row->bytes,
                'referrer' => $row->referrer,
                'user_agent' => $row->user_agent,
                'internal' => in_array($row->ip_address, $this->internalAddresses, true),
            ],
        ])->all();
    }

}
