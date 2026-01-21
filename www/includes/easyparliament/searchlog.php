<?php

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
class SEARCHLOG
{

    private $db = null;

    /**
     *
     */
    public function __construct() {
        $this->SEARCHURL = new URL('search');
        $this->db = new ParlDB();
    }

    /**
     *
     */
    public function add($searchlogdata)
    {

        $this->db->query("INSERT INTO search_query_log
            (query_string, page_number, count_hits, ip_address, query_time)
            VALUES ('" . mysqli_real_escape_string($db, $searchlogdata['query']) . "',
            '" . $searchlogdata['page'] . "', '" . $searchlogdata['hits'] . "',
            '" . getenv('REMOTE_ADDR') . "', NOW())");

    }

    /**
     * Select popular queries.
     */
    public function popular_recent($count)
    {

        $q = $this->db->query("
                SELECT query_string, count(*) AS c
                FROM search_query_log
                WHERE count_hits != 0
                    AND query_time > date_sub(NOW(), INTERVAL 1 DAY)
                GROUP BY query_string
                ORDER BY c desc
                LIMIT $count;");

        $popular_searches = [];
        for ($row = 0; $row < $q->rows(); $row++) {
            array_push($popular_searches, $this->_db_row_to_array($q, $row));
        }
        return $popular_searches;
    }

    /**
     *
     */
    public function _db_row_to_array($q, $row)
    {
        $query = $q->field($row, 'query_string');
        $this->SEARCHURL->insert(['s' => $query, 'pop' => 1]);
        $url = $this->SEARCHURL->generate();
        $htmlescape = 1;
        if ($pos = strpos($query, ':')) {
            $qq = $this->db->query('SELECT first_name, last_name FROM member WHERE person_id="' . mysqli_real_escape_string($db, substr($query, $pos + 1)) . '" LIMIT 1');
            if ($qq->rows()) {
                $query = $qq->field(0, 'first_name') . ' ' . $qq->field(0, 'last_name');
                $htmlescape = 0;
            }
        }
        $visible_name = preg_replace('/"/', '', $query);

        $rowarray = $q->row($row);
        $rowarray['query'] = $query;
        $rowarray['visible_name'] = $visible_name;
        $rowarray['url'] = $url;
        $rowarray['display'] = '<a href="' . $url . '">' . ($htmlescape ? htmlentities($visible_name) : $visible_name) . '</a>';

        return $rowarray;
    }

    /**
     *
     */
    public function admin_recent_searches($count)
    {

        $q = $this->db->query("SELECT query_string, page_number, count_hits, ip_address, query_time
                FROM search_query_log ORDER BY query_time desc LIMIT $count");
        $searches_array = [];
        for ($row = 0; $row < $q->rows(); $row++) {
            array_push($searches_array, $this->_db_row_to_array($q, $row));
        }
        return $searches_array;
    }

    /**
     *
     */
    public function admin_popular_searches($count)
    {

        $q = $this->db->query("SELECT *, count(*) AS c FROM search_query_log
                WHERE count_hits != 0 AND query_string NOT LIKE '%speaker:%'
                AND query_time > date_sub(NOW(), INTERVAL 30 DAY)
                GROUP BY query_string ORDER BY c desc LIMIT $count;");

        $popular_searches = [];
        for ($row = 0; $row < $q->rows(); $row++) {
            array_push($popular_searches, $this->_db_row_to_array($q, $row));
        }
        return $popular_searches;
    }

    /**
     *
     */
    public function admin_failed_searches()
    {

        $q = $this->db->query("SELECT query_string, page_number, count_hits, ip_address, query_time,
                COUNT(*) AS group_count, MIN(query_time) AS min_time, MAX(query_time) AS max_time,
                COUNT(distinct ip_address) as count_ips
                FROM search_query_log GROUP BY query_string HAVING count_hits = 0
                ORDER BY count_ips DESC, max_time DESC");
        $searches_array = [];
        for ($row = 0; $row < $q->rows(); $row++) {
            array_push($searches_array, $this->_db_row_to_array($q, $row));
        }
        return $searches_array;
    }

}

global $SEARCHLOG;
$SEARCHLOG = new SEARCHLOG();
