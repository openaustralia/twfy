<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personinfo Eloquent model.
 *
 * @property int $person_id
 * @property string $data_key
 * @property string $data_value
 * @property string $lastupdate
 */
class Personinfo extends Model {

    // Eloquent does not natively support composite primary keys, so this model
    // keeps a manual save-query override alongside the schema-level composite PK.
    private const COMPOSITE_PRIMARY_KEY = ['person_id', 'data_key'];

    protected $table = 'personinfo';

    public $incrementing = false;

    protected $primaryKey = self::COMPOSITE_PRIMARY_KEY;

    protected $fillable = [
        'person_id',
        'data_key',
        'data_value',
    ];

    /**
     * Apply the composite primary key when building update queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *   Update query builder.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *   Updated query builder.
     */
    protected function setKeysForSaveQuery($query): Builder {
        foreach (self::COMPOSITE_PRIMARY_KEY as $keyName) {
            $query->where($keyName, '=', $this->getAttribute($keyName));
        }

        return $query;
    }

    /**
     * Defines the relationship to member rows for this person.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Related member rows.
     */
    public function members(): HasMany {
        return $this->hasMany(Member::class, 'person_id', 'person_id');
    }

}
