<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Memberinfo Eloquent model.
 *
 * @property int $member_id
 * @property string $data_key
 * @property string $data_value
 * @property string $lastupdate
 */
class Memberinfo extends Model {

    protected $table = 'memberinfo';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'member_id',
        'data_key',
        'data_value',
    ];

}
