<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\UsageStats\ApiUsageReport;
use OpenAustralia\TWFY\UsageStats\DownloadReport;
use OpenAustralia\TWFY\UsageStats\ReasonText;
use OpenAustralia\TWFY\UsageStats\Settings;

/**
 * The admin report of the busiest API keys and data.openaustralia.org.au downloads.
 */
class UsageController extends AdminController {

    /**
     * The periods the page offers, in days, the default first.
     */
    public const PERIODS = [30, 7, 1];

    /**
     * The usage over a period ending today.
     */
    public function index(): void {
        $this->page('admin_usage', function () {
            $period = self::period((string) ($_GET['period'] ?? ''));
            [$from, $to] = self::range($period, new \DateTimeImmutable('today', new \DateTimeZone('UTC')));
            $downloads = new DownloadReport($from, $to, Settings::internalAddresses());

            return $this->view('admin.usage', [
                'filters' => array_map(
                    fn (int $days): array => [$days === 1 ? 'Today' : "Last $days days", $this->adminUrl('usage.php', ['period' => $days]), $days === $period],
                    self::PERIODS
                ),
                'from' => $from,
                'to' => $to,
                'keys' => array_map(fn (array $row): array => self::keyRow($row), ApiUsageReport::topKeys($from, $to)),
                'daily' => $downloads->dailyTotals(),
                'paths' => $downloads->topPaths(),
                'visitors' => $downloads->topVisitors(),
            ]);
        });
    }

    /**
     * The period asked for, if the page offers it, else the default.
     */
    public static function period(string $requested): int {
        return in_array((int) $requested, self::PERIODS, true) ? (int) $requested : self::PERIODS[0];
    }

    /**
     * The first and last day of a period ending today, as Y-m-d.
     *
     * Pass today in UTC, as PHP's own zone (Sydney) shifts relative days across daylight saving.
     *
     * @return string[]
     *   The first day, then the last.
     */
    public static function range(int $period, \DateTimeImmutable $today): array {
        return [$today->modify('-' . ($period - 1) . ' days')->format('Y-m-d'), $today->format('Y-m-d')];
    }

    /**
     * One row of the busiest API keys table.
     *
     * @param array<string, mixed> $row
     *   A row from ApiUsageReport::topKeys().
     *
     * @return array<string, mixed>
     *   What the view shows: the holder's name and address if they gave a web one, and the key's figures.
     */
    private static function keyRow(array $row): array {
        $key = $row['key'];
        $user = $key->user;
        $reason = trim(preg_replace('/\s+/u', ' ', (string) $key->reason));
        return [
            'name' => $user === null ? null : trim("$user->firstname $user->lastname"),
            'website' => $user !== null && preg_match('#^https?://#i', (string) $user->url) ? $user->url : null,
            'email' => $user->email ?? '',
            'reason' => $reason,
            'short_reason' => ReasonText::shorten($reason),
            'commercial' => (bool) $key->commercial,
            'disabled' => (string) $key->disabled,
            'total' => $row['total'],
            'mean' => $row['mean'],
            'min' => $row['min'],
            'max' => $row['max'],
        ];
    }

}
