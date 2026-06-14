<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Title Eloquent model.
 *
 * @property string $title
 */
class Title extends Model {

    protected $table = 'titles';

    protected $primaryKey = 'title';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
    ];

}
