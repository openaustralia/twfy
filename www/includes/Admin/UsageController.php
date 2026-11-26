<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\UsageStats\ApiUsageReport;
use OpenAustralia\TWFY\UsageStats\DownloadReport;
use OpenAustralia\TWFY\UsageStats\ReasonText;
use OpenAustralia\TWFY\UsageStats\Settings;

/**
 * The admin reports of the busiest API keys and data.openaustralia.org.au downloads.
 */
class UsageController extends AdminController {

    /**
     * The periods the page offers, in days, the default first.
     */
    public const PERIODS = [30, 7, 1];

    /**
     * The API keys that made the most calls over a period ending today.
     */
    public function apiKeys(): void {
        $this->page('admin_api_keys', function () {
            [$period, $from, $to] = self::periodAndRange();

            return $this->view('admin.api_keys', [
                'filters' => $this->periodFilters('api_keys.php', $period),
                'from' => $from,
                'to' => $to,
                'keys' => array_map(fn (array $row): array => self::keyRow($row), ApiUsageReport::topKeys($from, $to)),
            ]);
        });
    }

    /**
     * The data.openaustralia.org.au downloads over a period ending today.
     */
    public function downloads(): void {
        $this->page('admin_downloads', function () {
            [$period, $from, $to] = self::periodAndRange();
            $downloads = new DownloadReport($from, $to, Settings::internalAddresses());

            return $this->view('admin.downloads', [
                'filters' => $this->periodFilters('downloads.php', $period),
                'from' => $from,
                'to' => $to,
                'daily' => $downloads->dailyTotals(),
                'paths' => $downloads->topPaths(),
                'visitors' => $downloads->topVisitors(),
            ]);
        });
    }

    /**
     * The period asked for, and the first and last day of it.
     *
     * @return array{int, string, string}
     *   The period in days, then its first and last day as Y-m-d.
     */
    private static function periodAndRange(): array {
        $period = self::period((string) ($_GET['period'] ?? ''));
        return [$period, ...self::range($period, new \DateTimeImmutable('today', new \DateTimeZone('UTC')))];
    }

    /**
     * Links to each period on a page, with the current one marked.
     *
     * @param string $page
     *   The page's file name in the admin folder.
     * @param int $current
     *   The period shown, in days.
     *
     * @return list<array{string, string, bool}>
     *   Each choice's label, address and whether it is the current one.
     */
    private function periodFilters(string $page, int $current): array {
        return array_map(
            fn (int $days): array => [$days === 1 ? 'Today' : "Last $days days", $this->adminUrl($page, ['period' => $days]), $days === $current],
            self::PERIODS
        );
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
            'name' => $user === null ? null : $user->fullName(),
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
