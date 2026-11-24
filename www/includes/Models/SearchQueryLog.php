<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * SearchQueryLog Eloquent model.
 *
 * @property int $id
 * @property string|null $query_string
 * @property int|null $page_number
 * @property int|null $count_hits
 * @property string|null $ip_address
 * @property string|null $query_time
 */
class SearchQueryLog extends Model {

    protected $table = 'search_query_log';

    public $timestamps = true;

    protected $fillable = [
        'query_string',
        'page_number',
        'count_hits',
        'ip_address',
        'query_time',
    ];

    /**
     * The newest searches first.
     *
     * @param \Illuminate\Database\Eloquent\Builder<SearchQueryLog> $query
     *   The query to narrow.
     * @param int $limit
     *   How many searches.
     *
     * @return \Illuminate\Database\Eloquent\Builder<SearchQueryLog>
     *   The same query, newest first and limited.
     */
    public function scopeMostRecent(Builder $query, int $limit): Builder {
        return $query->orderByDesc('query_time')->limit($limit);
    }

    /**
     * Each query that found something in the last 30 days and how many times it was made, most made first.
     *
     * Speaker searches are left out. Each row has query_string and c.
     *
     * @param \Illuminate\Database\Eloquent\Builder<SearchQueryLog> $query
     *   The query to narrow.
     * @param int $limit
     *   How many queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder<SearchQueryLog>
     *   The same query, grouped by query string and limited.
     */
    public function scopePopular(Builder $query, int $limit): Builder {
        return $query->where('count_hits', '!=', 0)
          ->where('query_string', 'not like', '%speaker:%')
          ->whereRaw('query_time > DATE_SUB(NOW(), INTERVAL 30 DAY)')
          ->select('query_string')
          ->selectRaw('COUNT(*) AS c')
          ->groupBy('query_string')
          ->orderByDesc('c')
          ->limit($limit);
    }

    /**
     * Each query that found nothing and how often, by how many addresses made it and then how recently.
     *
     * Each row has query_string, group_count, min_time, max_time and count_ips.
     *
     * @param \Illuminate\Database\Eloquent\Builder<SearchQueryLog> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<SearchQueryLog>
     *   The same query, limited to searches without hits and grouped by query string.
     */
    public function scopeFailed(Builder $query): Builder {
        return $query->where('count_hits', 0)
          ->select('query_string')
          ->selectRaw('COUNT(*) AS group_count, MIN(query_time) AS min_time, MAX(query_time) AS max_time, COUNT(DISTINCT ip_address) AS count_ips')
          ->groupBy('query_string')
          ->orderByDesc('count_ips')
          ->orderByDesc('max_time');
    }

}
