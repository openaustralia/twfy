<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * The key these calls were made with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ApiKey, ApiDailyUsage>
     *   The relationship.
     */
    public function apiKey(): BelongsTo {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * Counts for UTC days from $from to $to, both included.
     *
     * @param \Illuminate\Database\Eloquent\Builder<ApiDailyUsage> $query
     *   The query to narrow.
     * @param string $from
     *   The first day, as Y-m-d.
     * @param string $to
     *   The last day, as Y-m-d.
     *
     * @return \Illuminate\Database\Eloquent\Builder<ApiDailyUsage>
     *   The same query, limited to those days.
     */
    public function scopeBetweenDays(Builder $query, string $from, string $to): Builder {
        return $query->whereBetween('date', [$from, $to]);
    }

}
