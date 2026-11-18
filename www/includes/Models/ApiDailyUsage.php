<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Number of API calls per key per UTC day.
 *
 * The primary key is (api_key_id, date), which Eloquent can't represent, so
 * write rows only with upsert() or query-builder calls, never save() on a
 * loaded row.
 *
 * @property int $api_key_id
 * @property \Illuminate\Support\Carbon $date
 * @property int $count
 */
class ApiDailyUsage extends Model {

    protected $table = 'api_daily_usage';

    protected $primaryKey = null;

    public $incrementing = false;

    /**
     * The database fills created_at and updated_at. Eloquent would use PHP's
     * clock, which the site sets to Sydney time while MySQL runs in UTC.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'api_key_id',
        'date',
        'count',
    ];

    protected $casts = [
        'date' => 'date',
        'count' => 'integer',
    ];

}
