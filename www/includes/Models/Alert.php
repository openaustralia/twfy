<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Alert Eloquent model.
 *
 * @property int $alert_id
 * @property string $email
 * @property string $criteria
 * @property int $deleted
 * @property string $registrationtoken
 * @property int $confirmed
 * @property string $created
 * @property int $recommended
 */
class Alert extends Model {

    protected $table = 'alerts';

    protected $primaryKey = 'alert_id';

    protected $fillable = [
        'email',
        'criteria',
        'deleted',
        'registrationtoken',
        'confirmed',
        'created',
        'recommended',
    ];

    protected $casts = [
        'deleted' => 'bool',
        'confirmed' => 'bool',
        'recommended' => 'bool',
    ];

    // PHP's clock is not the database's (#1040); the column defaults keep created_at and updated_at correct.
    public $timestamps = false;

    /**
     * Alerts that are confirmed and not deleted.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to active alerts.
     */
    public function scopeActive(Builder $query): Builder {
        return $query->where('confirmed', true)->where('deleted', false);
    }

    /**
     * Alerts that were deleted, confirmed or not.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to deleted alerts.
     */
    public function scopeDeletedAlerts(Builder $query): Builder {
        return $query->where('deleted', true);
    }

    /**
     * Alerts nobody has confirmed, deleted or not.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to unconfirmed alerts.
     */
    public function scopeUnconfirmedAlerts(Builder $query): Builder {
        return $query->where('confirmed', false);
    }

    /**
     * Alerts for one person, whose criteria start with "speaker:".
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to speaker alerts.
     */
    public function scopeSpeakerAlerts(Builder $query): Builder {
        return $query->where('criteria', 'like', 'speaker:%');
    }

    /**
     * Alerts whose criteria never mention a speaker.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to keyword alerts.
     */
    public function scopeKeywordAlerts(Builder $query): Builder {
        return $query->where('criteria', 'not like', '%speaker:%');
    }

    /**
     * One row per distinct criteria, with how many alerts share it as `c`,
     * most shared first.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to group.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, grouped by criteria.
     */
    public function scopeCountedByCriteria(Builder $query): Builder {
        return $query->select('criteria')
          ->selectRaw('COUNT(*) AS c')
          ->groupBy('criteria')
          ->orderByDesc('c');
    }

}
