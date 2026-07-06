<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gidredirect Eloquent model.
 *
 * @property string|null $gid_from
 * @property string|null $gid_to
 * @property string $hdate
 * @property int|null $major
 */
class Gidredirect extends Model {

    protected $table = 'gidredirect';

    public $incrementing = false;

    protected $primaryKey = 'gid_from';

    protected $keyType = 'string';

    protected $fillable = [
        'gid_from',
        'gid_to',
        'hdate',
        'major',
    ];

    protected $casts = [
        'hdate' => 'date',
    ];

}
