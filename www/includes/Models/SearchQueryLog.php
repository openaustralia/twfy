<?php

namespace OpenAustralia\TWFY\Models;

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

}
