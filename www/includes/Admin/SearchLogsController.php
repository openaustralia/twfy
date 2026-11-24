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
                'url' => $row['url'],
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
                'url' => $row['url'],
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
                'url' => $row['url'],
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
     * The searcher's host name, shortened.
     */
    private static function host(string $ip_address): string {
        $host = gethostbyaddr($ip_address);
        return trim_characters($host, strlen($host) - 25, 100);
    }

    /**
     * How long ago a time was, such as "2 hours".
     */
    private static function ago(string $datetime): string {
        return str_replace(' ago', '', relative_time($datetime));
    }

}
