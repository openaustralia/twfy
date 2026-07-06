<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Indexbatch Eloquent model.
 *
 * @property int $indexbatch_id
 * @property string|null $created
 */
class Indexbatch extends Model {

    protected $table = 'indexbatch';

    protected $primaryKey = 'indexbatch_id';

    protected $fillable = [
        'created',
    ];

}
