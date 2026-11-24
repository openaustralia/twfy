<?php

namespace OpenAustralia\TWFY\Admin;

/**
 * The admin reports on what people searched for.
 */
class SearchLogsController extends AdminController {

    /**
     * The most recent searches.
     */
    public function recent(): void {
        $this->page('admin_searchlogs', function () {
            global $SEARCHLOG;

            $searches = array_map(fn (array $row): array => [
                'url' => self::plainUrl($row['url']),
                'query' => $row['query'],
                'page' => $row['page_number'],
                'hits' => $row['count_hits'],
                'host' => self::host($row['ip_address']),
                'ago' => self::ago($row['query_time']),
            ], $SEARCHLOG->admin_recent_searches(50));

            return $this->view('admin.searchlogs', ['searches' => $searches]);
        });
    }

    /**
     * The searches with results that people made most in the last 30 days.
     */
    public function popular(): void {
        $this->page('admin_popularsearches', function () {
            global $SEARCHLOG;

            $searches = array_map(fn (array $row): array => [
                'url' => self::plainUrl($row['url']),
                'query' => $row['query'],
                'count' => $row['c'],
            ], $SEARCHLOG->admin_popular_searches(1000));

            return $this->view('admin.popularsearches', ['searches' => $searches]);
        });
    }

    /**
     * The searches that found nothing.
     */
    public function failed(): void {
        $this->page('admin_failedsearches', function () {
            global $SEARCHLOG;

            $searches = array_map(fn (array $row): array => [
                'url' => self::plainUrl($row['url']),
                'query' => $row['query'],
                'ips' => $row['count_ips'],
                'times' => $row['group_count'],
                'latest' => self::ago($row['max_time']),
                'first' => self::ago($row['min_time']),
            ], $SEARCHLOG->admin_failed_searches());

            return $this->view('admin.failedsearches', ['searches' => $searches]);
        });
    }

    /**
     * A search address without the HTML encoding SEARCHLOG gives it, as Blade encodes what it prints.
     */
    private static function plainUrl(string $url): string {
        return htmlspecialchars_decode($url);
    }

    /**
     * The searcher's host name, shortened, or nothing when the address was not kept.
     */
    private static function host(?string $ip_address): string {
        if ($ip_address === null || $ip_address === '') {
            return '';
        }
        $host = gethostbyaddr($ip_address);
        return trim_characters($host, strlen($host) - 25, 100);
    }

    /**
     * How long ago a time was, such as "2 hours", or nothing when the time was not kept.
     */
    private static function ago(?string $datetime): string {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        return str_replace(' ago', '', relative_time($datetime));
    }

}
