<?php

/**
 * @file
 * Eloquent model for epobject table.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Epobject Eloquent model.
 *
 * @property int $epobject_id
 * @property string|null $title
 * @property string|null $body
 * @property int|null $type
 * @property string|null $created
 * @property string|null $modified
 */
class Epobject extends Model {

    protected $table = 'epobject';

    protected $primaryKey = 'epobject_id';

    public $timestamps = false;

    protected $fillable = [
        'epobject_id',
        'title',
        'body',
        'type',
        'created',
        'modified',
    ];
}
