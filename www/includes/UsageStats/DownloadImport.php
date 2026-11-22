<?php

namespace OpenAustralia\TWFY\UsageStats;

use Illuminate\Database\Capsule\Manager as DB;
use OpenAustralia\TWFY\Models\DataDownload;

/**
 * Copies a data.openaustralia.org.au access log into data_downloads.
 */
class DownloadImport {

    private const CHUNK = 1000;

    /**
     * Imports the lines newer than any already imported, so a file can be run twice.
     *
     * Files must be imported oldest first. One transaction, so a failure part
     * way through leaves nothing for the next run to skip past.
     *
     * @param string $path
     *   A log file, plain or gzipped.
     *
     * @return array<string, int>
     *   Lines imported, already imported, and not in combined format.
     */
    public static function fromFile(string $path): array {
        $handle = is_readable($path) ? gzopen($path, 'rb') : false;
        if ($handle === false) {
            throw new \RuntimeException("Can't open $path");
        }

        $counts = ['imported' => 0, 'already_imported' => 0, 'unreadable' => 0];
        try {
            DB::transaction(function () use ($handle, &$counts) {
                $newest = DataDownload::max('request_time');
                $rows = [];
                while (($line = gzgets($handle)) !== false) {
                    $row = AccessLogLine::parse(rtrim($line, "\r\n"));
                    if ($row === null) {
                        $counts['unreadable']++;
                    } elseif ($newest !== null && $row['request_time'] <= $newest) {
                        $counts['already_imported']++;
                    } else {
                        $rows[] = $row;
                        if (count($rows) === self::CHUNK) {
                            DataDownload::insert($rows);
                            $counts['imported'] += count($rows);
                            $rows = [];
                        }
                    }
                }
                if ($rows) {
                    DataDownload::insert($rows);
                    $counts['imported'] += count($rows);
                }
            });
        } finally {
            gzclose($handle);
        }
        return $counts;
    }

}
