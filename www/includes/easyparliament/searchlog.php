<?php

require_once __DIR__ . '/../request.php';

use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\SearchQueryLog;

/**
 * For doing stuff with searchlogs.
 *
 * To add a new searchlog do this:
 * global $SEARCHLOG;
 * $SEARCHLOG->add(
 * array('query' => $searchstring,
 * 'page' => $page,
 * 'hits' => $count));
 * The date/time and IP address are automatically stored.
 *
 * To get the ten most popular searches in the last day:
 * global $SEARCHLOG;
 * $popular_searches = $SEARCHLOG->popular_recent(10);
 * The return value is an array.  Each element of the form
 * array(  'query' => '"new york"',
 * 'visible_name' => 'new york',
 * 'url' => 'http://www.openaustralia.org/search/?s=%22new+york%22&pop=1',
 * 'display' => '<a href="http://www.openaustralia.org/search/?s=%22new+york%22&pop=1">new york</a>")
 * Note that the url includes "pop=1" which stops popular searches feeding back
 * into being more popular.
 */
class SEARCHLOG {

    private $SEARCHURL = null;

    /**
     *
     */
    public function __construct() {
        $this->SEARCHURL = new URL('search');

    }

    /**
     *
     */
    public function add($searchlogdata) {
        // Deduplicate repeated terms before storing.
        $query = implode(' ', array_unique(explode(' ', $searchlogdata['query'])));

        SearchQueryLog::create([
            'query_string' => $query,
            'page_number' => $searchlogdata['page'],
            'count_hits' => $searchlogdata['hits'],
            'ip_address' => ip_address(),
            'query_time' => date('Y-m-d H:i:s'),
        ]);

    }

    /**
     * Select popular queries.
     */
    public function popular_recent($count) {

        $rows = SearchQueryLog::where('count_hits', '!=', 0)
          ->whereRaw('query_time > date_sub(NOW(), INTERVAL 1 DAY)')
          ->groupBy('query_string')
          ->orderByDesc('c')
          ->limit($count)
          ->selectRaw('query_string, count(*) AS c')
          ->get();

        $popular_searches = [];
        foreach ($rows as $row) {
            $popular_searches[] = $this->_row_to_array($row);
        }
        return $popular_searches;
    }

    /**
     * Convert a row object to a display array.
     */
    public function _row_to_array($row) {
        $query = $row->query_string;
        // Deduplicate repeated terms (e.g. from bots hitting search with duplicated queries)
        $query = implode(' ', array_unique(explode(' ', $query)));
        $this->SEARCHURL->insert(['s' => $query, 'pop' => 1]);
        $url = $this->SEARCHURL->generate();
        $htmlescape = 1;
        if ($pos = strpos($query, ':')) {
            $member = Member::where('person_id', substr($query, $pos + 1))
              ->first(['first_name', 'last_name']);
            if ($member) {
                $query = $member->first_name . ' ' . $member->last_name;
                $htmlescape = 0;
            }
        }
        $visible_name = preg_replace('/"/', '', $query);

        $rowarray = (array) $row->getAttributes();
        $rowarray['query'] = $query;
        $rowarray['visible_name'] = $visible_name;
        $rowarray['url'] = $url;
        $rowarray['display'] = '<a href="' . $url . '">' . ($htmlescape ? htmlentities($visible_name) : $visible_name) . '</a>';

        return $rowarray;
    }

    /**
     *
     */
    public function admin_recent_searches($count) {

        $rows = SearchQueryLog::orderByDesc('query_time')
          ->limit($count)
          ->get(['query_string', 'page_number', 'count_hits', 'ip_address', 'query_time']);
        $searches_array = [];
        foreach ($rows as $row) {
            $searches_array[] = $this->_row_to_array($row);
        }
        return $searches_array;
    }

    /**
     *
     */
    public function admin_popular_searches($count) {

        $rows = SearchQueryLog::where('count_hits', '!=', 0)
          ->where('query_string', 'NOT LIKE', '%speaker:%')
          ->whereRaw('query_time > date_sub(NOW(), INTERVAL 30 DAY)')
          ->groupBy('query_string')
          ->orderByDesc('c')
          ->limit($count)
          ->selectRaw('query_string, count(*) AS c')
          ->get();

        $popular_searches = [];
        foreach ($rows as $row) {
            $popular_searches[] = $this->_row_to_array($row);
        }
        return $popular_searches;
    }

    /**
     *
     */
    public function admin_failed_searches() {

        $rows = SearchQueryLog::where('count_hits', 0)
          ->groupBy('query_string')
          ->orderByDesc('count_ips')
          ->orderByDesc('max_time')
          ->selectRaw('query_string, COUNT(*) AS group_count, MIN(query_time) AS min_time, MAX(query_time) AS max_time, COUNT(DISTINCT ip_address) AS count_ips')
          ->get();
        $searches_array = [];
        foreach ($rows as $row) {
            $searches_array[] = $this->_row_to_array($row);
        }
        return $searches_array;
    }

}
