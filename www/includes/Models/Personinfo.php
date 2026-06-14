<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Personinfo Eloquent model.
 *
 * @property int $person_id
 * @property string $data_key
 * @property string $data_value
 * @property string $lastupdate
 */
class Personinfo extends Model {

    protected $table = 'personinfo';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'person_id',
        'data_key',
        'data_value',
    ];

}
