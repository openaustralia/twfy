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

    protected function setKeysForSaveQuery($query): Builder {
        foreach (self::COMPOSITE_PRIMARY_KEY as $keyName) {
            $query->where($keyName, '=', $this->getAttribute($keyName));
        }

        return $query;
    }

    public function members(): HasMany {
        return $this->hasMany(Member::class, 'person_id', 'person_id');
    }

}
