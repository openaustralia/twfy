<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\DataDownload;

/**
 * data.openaustralia.org.au downloads over a run of UTC days.
 */
class DownloadReport {

    private string $from;

    private string $to;

    /**
     * @var string[]
     */
    private array $internalAddresses;

    /**
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     * @param string[] $internalAddresses
     *   IP addresses of OAF's own servers, counted separately.
     */
    public function __construct(string $from, string $to, array $internalAddresses) {
        $this->from = $from;
        $this->to = $to;
        $this->internalAddresses = $internalAddresses;
    }

    /**
     * Downloads per day, newest first.
     *
     * @return array<int, array<string, mixed>>
     *   Each with its 'day', 'downloads' and how many came from OAF's
     *   servers ('internal').
     */
    public function dailyTotals(): array {
        $query = DataDownload::betweenDays($this->from, $this->to)
          ->selectRaw('DATE(request_time) AS day, COUNT(*) AS downloads')
          ->groupByRaw('DATE(request_time)')
          ->orderByDesc('day');
        if ($this->internalAddresses) {
            $placeholders = implode(', ', array_fill(0, count($this->internalAddresses), '?'));
            $query->selectRaw("SUM(ip_address IN ($placeholders)) AS internal", $this->internalAddresses);
        }
        return $query->get()->map(fn (DataDownload $row) => [
            'day' => $row->day,
            'downloads' => (int) $row->downloads,
            'internal' => (int) ($row->internal ?? 0),
        ])->all();
    }

    /**
     * The most downloaded paths.
     *
     * @return array<int, array<string, mixed>>
     *   Each with its 'path' and 'downloads'.
     */
    public function topPaths(int $limit = 50): array {
        return DataDownload::betweenDays($this->from, $this->to)
          ->select('path')
          ->selectRaw('COUNT(*) AS downloads')
          ->groupBy('path')
          ->orderByDesc('downloads')
          ->orderBy('path')
          ->limit($limit)
          ->get()
          ->map(fn (DataDownload $row) => ['path' => $row->path, 'downloads' => (int) $row->downloads])
          ->all();
    }

    /**
     * The visitors with the most downloads, by IP address and user agent.
     *
     * @return array<int, array<string, mixed>>
     *   Each with its 'ip_address', 'user_agent', 'downloads' and whether
     *   it is one of OAF's servers ('internal').
     */
    public function topVisitors(int $limit = 50): array {
        return DataDownload::betweenDays($this->from, $this->to)
          ->select('ip_address', 'user_agent')
          ->selectRaw('COUNT(*) AS downloads')
          ->groupBy('ip_address', 'user_agent')
          ->orderByDesc('downloads')
          ->orderBy('ip_address')
          ->limit($limit)
          ->get()
          ->map(fn (DataDownload $row) => [
              'ip_address' => $row->ip_address,
              'user_agent' => $row->user_agent,
              'downloads' => (int) $row->downloads,
              'internal' => in_array($row->ip_address, $this->internalAddresses, true),
          ])
          ->all();
    }

}
