<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * The period and table rows for the admin usage page, as escaped HTML.
 */
class UsagePage {

    /**
     * The periods the page offers, in days, the default first.
     */
    public const PERIODS = [30, 7, 1];

    /**
     * The period asked for, if the page offers it, else the default.
     */
    public static function period(string $requested): int {
        return in_array((int) $requested, self::PERIODS, true) ? (int) $requested : self::PERIODS[0];
    }

    /**
     * The first and last day of a period ending today, as Y-m-d.
     *
     * Pass today in UTC: PHP's own zone (Sydney) shifts relative days across
     * daylight saving.
     *
     * @return string[]
     *   The first day, then the last.
     */
    public static function range(int $period, \DateTimeImmutable $today): array {
        return [$today->modify('-' . ($period - 1) . ' days')->format('Y-m-d'), $today->format('Y-m-d')];
    }

    /**
     * Links to the other periods, with the current one in bold.
     *
     * @param int $period
     *   The current period, in days.
     * @param callable $href
     *   Returns the escaped address of the page for a number of days.
     */
    public static function periodLinks(int $period, callable $href): string {
        return implode(' | ', array_map(function (int $days) use ($period, $href) {
            $label = $days === 1 ? 'Today' : "Last $days days";
            return $days === $period ? "<strong>$label</strong>" : '<a href="' . $href($days) . '">' . $label . '</a>';
        }, self::PERIODS));
    }

    /**
     * The page's tables, each with its header, rows and text for no rows.
     *
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     * @param string[] $internal
     *   IP addresses of OAF's own servers.
     *
     * @return array<string, array{0: string[], 1: array<int, string[]>, 2: string}>
     *   The tables, by heading.
     */
    public static function tables(string $from, string $to, array $internal): array {
        $downloads = new DownloadReport($from, $to, $internal);
        $none = 'No downloads imported for this period.';
        return [
            'Busiest API keys' => [
                ['Name', 'Email', 'Reason', 'Commercial', 'Disabled', 'Calls', 'Mean per day', 'Fewest', 'Most'],
                self::keyRows(ApiUsageReport::topKeys($from, $to)),
                'No API calls with a known key in this period.',
            ],
            'Data site downloads per day' => [['Day', 'Downloads', 'From OAF servers'], self::dailyRows($downloads->dailyTotals()), $none],
            'Most downloaded files' => [['Path', 'Downloads'], self::pathRows($downloads->topPaths()), $none],
            'Busiest visitors' => [
                ['IP address', 'User agent', 'Downloads', 'OAF server'],
                self::visitorRows($downloads->topVisitors()),
                $none,
            ],
        ];
    }

    /**
     * The key holder's name, linked to the web address they gave if it's http(s).
     */
    public static function name(?object $user): string {
        if ($user === null) {
            return '<em>No account</em>';
        }
        $name = self::text(trim("$user->firstname $user->lastname"));
        return preg_match('#^https?://#i', (string) $user->url) ? '<a href="' . self::text($user->url) . '">' . $name . '</a>' : $name;
    }

    /**
     * Rows for the busiest API keys table.
     *
     * @param array<int, array<string, mixed>> $topKeys
     *   ApiUsageReport::topKeys()'s rows.
     *
     * @return array<int, string[]>
     *   The rows.
     */
    public static function keyRows(array $topKeys): array {
        return array_map(function (array $row) {
            $key = $row['key'];
            $reason = trim(preg_replace('/\s+/u', ' ', (string) $key->reason));
            return [
                self::name($key->user),
                self::text($key->user->email ?? ''),
                '<span title="' . self::text($reason) . '">' . self::text(ReasonText::shorten($reason)) . '</span>',
                $key->commercial ? 'Yes' : 'No',
                self::text((string) $key->disabled),
                number_format($row['total']),
                number_format($row['mean'], 1),
                number_format($row['min']),
                number_format($row['max']),
            ];
        }, $topKeys);
    }

    /**
     * Rows for the downloads per day table.
     *
     * @param array<int, array<string, mixed>> $totals
     *   DownloadReport::dailyTotals()'s rows.
     *
     * @return array<int, string[]>
     *   The rows.
     */
    public static function dailyRows(array $totals): array {
        return array_map(fn (array $row) => [
            self::text($row['day']),
            number_format($row['downloads']),
            number_format($row['internal']),
        ], $totals);
    }

    /**
     * Rows for the most downloaded files table.
     *
     * @param array<int, array<string, mixed>> $paths
     *   DownloadReport::topPaths()'s rows.
     *
     * @return array<int, string[]>
     *   The rows.
     */
    public static function pathRows(array $paths): array {
        return array_map(fn (array $row) => [
            self::text($row['path']),
            number_format($row['downloads']),
        ], $paths);
    }

    /**
     * Rows for the busiest visitors table.
     *
     * @param array<int, array<string, mixed>> $visitors
     *   DownloadReport::topVisitors()'s rows.
     *
     * @return array<int, string[]>
     *   The rows.
     */
    public static function visitorRows(array $visitors): array {
        return array_map(fn (array $row) => [
            self::text($row['ip_address']),
            self::text($row['user_agent']),
            number_format($row['downloads']),
            $row['internal'] ? 'Yes' : 'No',
        ], $visitors);
    }

    /**
     * Escapes text for HTML.
     */
    private static function text(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

}
