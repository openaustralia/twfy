<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;

/**
 * The busiest API keys over a run of UTC days, from the daily counts.
 */
class ApiUsageReport {

    /**
     * The keys with the most calls, with their mean, fewest and most per day.
     *
     * Days without calls count as zero. Ties are ordered by key, so the list
     * stays the same from one page load to the next.
     *
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     * @param int $limit
     *   How many keys.
     *
     * @return array<int, array<string, mixed>>
     *   Each with the key ('key', its user loaded) and 'total', 'mean',
     *   'min' and 'max' calls.
     */
    public static function topKeys(string $from, string $to, int $limit = 50): array {
        $days = (int) (new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days + 1;
        $rows = ApiDailyUsage::betweenDays($from, $to)
          ->whereHas('apiKey')
          ->select('api_key_id')
          ->selectRaw('SUM(count) AS total, COUNT(*) AS days_used, MIN(count) AS fewest, MAX(count) AS most')
          ->groupBy('api_key_id')
          ->orderByDesc('total')
          ->orderBy('api_key_id')
          ->limit($limit)
          ->get();
        $keys = ApiKey::with('user')->whereIn('id', $rows->pluck('api_key_id'))->get()->keyBy('id');

        return $rows->map(fn (ApiDailyUsage $row) => [
            'key' => $keys[$row->api_key_id],
            'total' => (int) $row->total,
            'mean' => $row->total / $days,
            'min' => $row->days_used < $days ? 0 : (int) $row->fewest,
            'max' => (int) $row->most,
        ])->all();
    }

}
