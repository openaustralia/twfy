<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One request to data.openaustralia.org.au, imported from Apache's log.
 *
 * @property int $id
 * @property string $request_time
 *   UTC.
 * @property string $ip_address
 * @property string $method
 * @property string $path
 * @property int $status
 * @property int $bytes
 * @property string $referrer
 * @property string $user_agent
 * @property string $line_hash
 *   Identifies the log line, so a file imported twice adds nothing.
 */
class DataDownload extends Model {

    protected $table = 'data_downloads';

    /**
     * The database fills created_at and updated_at. Eloquent would use PHP's
     * clock, which the site sets to Sydney time while MySQL runs in UTC.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'request_time',
        'ip_address',
        'method',
        'path',
        'status',
        'bytes',
        'referrer',
        'user_agent',
        'line_hash',
    ];

    protected $casts = [
        'status' => 'integer',
        'bytes' => 'integer',
    ];

    /**
     * Downloads on UTC days from $from to $to, both included.
     *
     * @param \Illuminate\Database\Eloquent\Builder<DataDownload> $query
     *   The query to narrow.
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     *
     * @return \Illuminate\Database\Eloquent\Builder<DataDownload>
     *   The same query, limited to those days.
     */
    public function scopeBetweenDays(Builder $query, string $from, string $to): Builder {
        $end = (new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
        return $query->where('request_time', '>=', "$from 00:00:00")->where('request_time', '<', "$end 00:00:00");
    }

}
