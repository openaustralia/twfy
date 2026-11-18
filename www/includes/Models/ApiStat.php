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

    /**
     * The database fills created_at and updated_at. Eloquent would use PHP's
     * clock, which the site sets to Sydney time while MySQL runs in UTC.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'api_key',
        'ip_address',
        'query_time',
        'query',
    ];

}
