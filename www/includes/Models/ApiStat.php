<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ApiStat Eloquent model.
 *
 * @property int $id
 * @property string $api_key
 * @property string $ip_address
 * @property string $query_time
 * @property string $query
 */
class ApiStat extends Model {

    protected $table = 'api_stats';

    protected $fillable = [
        'api_key',
        'ip_address',
        'query_time',
        'query',
    ];

}
