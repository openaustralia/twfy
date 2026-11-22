<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;

/**
 * Recounts api_daily_usage from api_stats, for the days before counting began.
 *
 * Counts only known keys, as api_log_call() does. Today is left to
 * api_log_call(), which is still adding to it.
 */
class DailyUsageRebuild {

    private const WRITE_CHUNK = 1000;

    /**
     * Replaces the count for every key and UTC day before today.
     *
     * Reads api_stats by id range, as query_time has no index.
     *
     * @param int $chunk
     *   Ids read per query.
     * @param callable|null $progress
     *   Called with the last id read after each query.
     *
     * @return array<string, int>
     *   Rows read and key-days written.
     */
    public static function run(int $chunk = 100000, ?callable $progress = null): array {
        $today = gmdate('Y-m-d');
        // MySQL compares keys without regard to case, as api_log_call()'s lookup does.
        $keyIds = array_change_key_case(ApiKey::pluck('id', 'api_key')->all());
        $maxId = (int) ApiStat::max('id');

        $counts = [];
        $read = 0;
        for ($afterId = 0; $afterId < $maxId; $afterId += $chunk) {
            $rows = ApiStat::where('id', '>', $afterId)
              ->where('id', '<=', $afterId + $chunk)
              ->selectRaw('api_key, DATE(query_time) AS day, COUNT(*) AS calls')
              ->groupBy('api_key')
              ->groupByRaw('DATE(query_time)')
              ->get();
            foreach ($rows as $row) {
                $read += (int) $row->calls;
                $keyId = $keyIds[strtolower($row->api_key)] ?? null;
                if ($keyId !== null && $row->day < $today) {
                    $counts[$keyId][$row->day] = ($counts[$keyId][$row->day] ?? 0) + (int) $row->calls;
                }
            }
            if ($progress) {
                $progress(min($afterId + $chunk, $maxId));
            }
        }

        $written = 0;
        $batch = [];
        foreach ($counts as $keyId => $days) {
            foreach ($days as $day => $count) {
                $batch[] = ['api_key_id' => $keyId, 'date' => $day, 'count' => $count];
                if (count($batch) === self::WRITE_CHUNK) {
                    $written += self::write($batch);
                    $batch = [];
                }
            }
        }
        $written += self::write($batch);

        return ['rows_read' => $read, 'key_days_written' => $written];
    }

    /**
     * Writes counts, replacing any already there.
     *
     * @param array<int, array<string, mixed>> $rows
     *   Counts as api_daily_usage rows.
     */
    private static function write(array $rows): int {
        if ($rows) {
            ApiDailyUsage::upsert($rows, ['api_key_id', 'date'], ['count']);
        }
        return count($rows);
    }

}
