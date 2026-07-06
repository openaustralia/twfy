<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Consinfo Eloquent model.
 *
 * @property string $constituency
 * @property string $data_key
 * @property string $data_value
 */
class Consinfo extends Model {

    protected $table = 'consinfo';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'constituency',
        'data_key',
        'data_value',
    ];

}
