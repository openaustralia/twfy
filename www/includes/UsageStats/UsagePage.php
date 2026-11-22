<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\AdminView;

/**
 * The HTML of the admin usage page, in the shared admin style. Everything it returns is escaped.
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
     * The whole page body: the breadcrumbs, the periods, a note on the data and the tables.
     *
     * @param int $period
     *   The period shown, in days.
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     * @param string[] $internal
     *   IP addresses of OAF's own servers.
     */
    public static function page(int $period, string $from, string $to, array $internal): string {
        $html = AdminView::breadcrumbs([['Admin', '/admin/'], ['API and data site usage', null]]);
        $html .= self::periodLinks($period);
        $html .= '<p class="' . AdminView::NOTE_CLASSES . ' max-w-3xl">Days are UTC, from ' . self::text($from) . ' to ' . self::text($to) . '. '
            . 'API calls are counted as they happen. Downloads are imported from data.openaustralia.org.au\'s access log each night, so they run to yesterday.</p>';
        foreach (self::tables($from, $to, $internal) as $heading => [$columns, $rows, $empty]) {
            $html .= '<h3 class="' . AdminView::HEADING_CLASSES . '">' . $heading . '</h3>'
                . AdminView::table(array_keys($columns), $rows, $empty, array_keys(array_filter(array_values($columns))));
        }
        return $html;
    }

    /**
     * Links to the other periods, with the current one marked as the page you are on.
     *
     * @param int $period
     *   The current period, in days.
     */
    public static function periodLinks(int $period): string {
        $html = '<ul class="flex flex-wrap items-center gap-6 !m-0 p-0 list-none text-sm font-semibold">';
        foreach (self::PERIODS as $days) {
            $label = $days === 1 ? 'Today' : "Last $days days";
            $html .= $days === $period
                ? '<li class="!ml-0 text-slate-900" aria-current="page">' . $label . '</li>'
                : '<li class="!ml-0"><a class="' . AdminView::LINK_CLASSES . '" href="' . self::text(AdminView::link('usage.php', ['period' => $days])) . '">' . $label . '</a></li>';
        }
        return $html . '</ul>';
    }

    /**
     * The page's tables, each with its columns, rows and text for no rows.
     *
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     * @param string[] $internal
     *   IP addresses of OAF's own servers.
     *
     * @return array<string, array{0: array<string, bool>, 1: array<int, string[]>, 2: string}>
     *   The tables, by heading.
     */
    public static function tables(string $from, string $to, array $internal): array {
        $downloads = new DownloadReport($from, $to, $internal);
        $none = 'No downloads imported for this period.';
        return [
            'Busiest API keys' => [
                [
                    'Name' => false,
                    'Email' => false,
                    'Reason' => false,
                    'Commercial' => false,
                    'Disabled' => false,
                    'Calls' => true,
                    'Mean per day' => true,
                    'Fewest' => true,
                    'Most' => true,
                ],
                self::keyRows(ApiUsageReport::topKeys($from, $to)),
                'No API calls with a known key in this period.',
            ],
            'Data site downloads per day' => [
                ['Day' => false, 'Downloads' => true, 'From OAF servers' => true],
                self::dailyRows($downloads->dailyTotals()),
                $none,
            ],
            'Most downloaded files' => [['Path' => false, 'Downloads' => true], self::pathRows($downloads->topPaths()), $none],
            'Busiest visitors' => [
                ['IP address' => false, 'User agent' => false, 'Downloads' => true, 'OAF server' => false],
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
        return preg_match('#^https?://#i', (string) $user->url) ? '<a class="' . AdminView::LINK_CLASSES . '" href="' . self::text($user->url) . '">' . $name . '</a>' : $name;
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
