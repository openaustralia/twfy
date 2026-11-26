<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * An address's alerts for everything one person says.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $query
     *   The query to narrow.
     * @param string $email
     *   The address.
     * @param int $personId
     *   The person.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Alert>
     *   The same query, limited to those alerts.
     */
    public function scopeFollowing(Builder $query, string $email, int $personId): Builder {
        return $query->where('email', $email)->where('criteria', "speaker:$personId");
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

    /**
     * The person whose speeches this alert is about, if it names one.
     */
    public function personId(): ?int {
        return preg_match('/(?:^| )speaker:(\d+)/', $this->criteria, $matches) ? (int) $matches[1] : null;
    }

    /**
     * The words this alert looks for, which are empty for an alert about a person alone.
     */
    public function keywords(): string {
        return trim(preg_replace('/(?:^| )speaker:\d+/', '', $this->criteria));
    }

    /**
     * The path of the search that finds what this alert looks for.
     */
    public function searchPath(): string {
        return '/search/?' . http_build_query(array_filter(['pid' => $this->personId(), 's' => $this->keywords()]));
    }

    /**
     * What the unsubscribe and confirm pages take to act on this alert.
     */
    protected function token(): Attribute {
        return Attribute::get(fn (): string => "$this->alert_id-$this->registrationtoken");
    }

}
