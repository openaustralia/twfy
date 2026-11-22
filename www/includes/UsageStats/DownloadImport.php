<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\DataDownload;

/**
 * Copies a data.openaustralia.org.au access log into data_downloads.
 */
class DownloadImport {

    private const CHUNK = 1000;

    /**
     * Imports each line not already imported, so a file can be run twice.
     *
     * A line is known by a hash of its text and how many identical lines came
     * before it in the file, so two identical requests in one second both
     * count, and a request logged after rotation still lands in the next file.
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
        $seen = [];
        $rows = [];
        try {
            while (($line = gzgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                $row = AccessLogLine::parse($line);
                if ($row === null) {
                    $counts['unreadable']++;
                    continue;
                }
                $text = sha1($line);
                $seen[$text] = ($seen[$text] ?? 0) + 1;
                $rows[] = $row + ['line_hash' => sha1($text . ':' . $seen[$text])];
                if (count($rows) === self::CHUNK) {
                    self::insert($rows, $counts);
                    $rows = [];
                }
            }
            self::insert($rows, $counts);
        } finally {
            gzclose($handle);
        }
        return $counts;
    }

    /**
     * Inserts rows whose line_hash isn't already there, and counts both kinds.
     *
     * @param array<int, array<string, mixed>> $rows
     *   Parsed lines, each with its line_hash.
     * @param array<string, int> $counts
     *   The counts to add to.
     */
    private static function insert(array $rows, array &$counts): void {
        if (!$rows) {
            return;
        }
        $inserted = DataDownload::insertOrIgnore($rows);
        $counts['imported'] += $inserted;
        $counts['already_imported'] += count($rows) - $inserted;
    }

}
