<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\SearchQueryLog;

/**
 * The admin reports on what people searched for.
 */
class SearchLogsController extends AdminController {

    /**
     * The most recent searches.
     */
    public function recent(): void {
        $this->page('admin_searchlogs', function () {
            $searches = SearchQueryLog::mostRecent(50)->get()->map(fn (SearchQueryLog $log): array => [
                'url' => self::searchUrl($log->query_string),
                'query' => self::label($log->query_string),
                'page' => $log->page_number,
                'hits' => $log->count_hits,
                'host' => self::host($log->ip_address),
                'ago' => self::ago($log->query_time),
            ])->all();

            return $this->view('admin.searchlogs', ['searches' => $searches]);
        });
    }

    /**
     * The searches with results that people made most in the last 30 days.
     */
    public function popular(): void {
        $this->page('admin_popularsearches', function () {
            $searches = SearchQueryLog::popular(1000)->get()->map(fn (SearchQueryLog $log): array => [
                'url' => self::searchUrl($log->query_string),
                'query' => self::label($log->query_string),
                'count' => $log->c,
            ])->all();

            return $this->view('admin.popularsearches', ['searches' => $searches]);
        });
    }

    /**
     * The searches that found nothing.
     */
    public function failed(): void {
        $this->page('admin_failedsearches', function () {
            $searches = SearchQueryLog::failed()->get()->map(fn (SearchQueryLog $log): array => [
                'url' => self::searchUrl($log->query_string),
                'query' => self::label($log->query_string),
                'ips' => $log->count_ips,
                'times' => $log->group_count,
                'latest' => self::ago($log->max_time),
                'first' => self::ago($log->min_time),
            ])->all();

            return $this->view('admin.failedsearches', ['searches' => $searches]);
        });
    }

    /**
     * The address of a search, with each repeated word once, and flagged as an admin's.
     */
    private static function searchUrl(?string $query): string {
        return WEBPATH . 'search/?' . http_build_query(['s' => self::withoutRepeats($query), 'pop' => 1]);
    }

    /**
     * What to call a search: the person's name for a speaker search, else the words as typed, quotes included, as
     * "cost of living" and cost of living are counted separately.
     */
    private static function label(?string $query): string {
        $query = self::withoutRepeats($query);
        $position = strpos($query, ':');
        if ($position) {
            $member = Member::where('person_id', substr($query, $position + 1))->first(['first_name', 'last_name']);
            if ($member) {
                return $member->first_name . ' ' . $member->last_name;
            }
        }
        return $query;
    }

    /**
     * A query with each word once, as bots send the same word many times.
     */
    private static function withoutRepeats(?string $query): string {
        return implode(' ', array_unique(explode(' ', (string) $query)));
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
