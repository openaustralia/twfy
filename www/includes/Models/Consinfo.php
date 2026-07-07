<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consinfo Eloquent model.
 *
 * @property string $constituency
 * @property string $data_key
 * @property string $data_value
 */
class Consinfo extends Model {

    // Eloquent does not natively support composite primary keys, so this model
    // keeps a manual save-query override alongside the schema-level composite PK.
    private const COMPOSITE_PRIMARY_KEY = ['constituency', 'data_key'];

    protected $table = 'consinfo';

    public $incrementing = false;

    protected $primaryKey = self::COMPOSITE_PRIMARY_KEY;

    protected $fillable = [
        'constituency',
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
     * Defines the relationship to constituency rows sharing this constituency name.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Matching constituency rows.
     */
    public function constituencies(): HasMany {
        return $this->hasMany(Constituency::class, 'name', 'constituency');
    }

}
