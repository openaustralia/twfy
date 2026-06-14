<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Anonvote Eloquent model.
 *
 * @property int $epobject_id
 * @property int $yes_votes
 * @property int $no_votes
 */
class Anonvote extends Model {

    protected $table = 'anonvotes';

    protected $primaryKey = 'epobject_id';

    public $incrementing = false;

    protected $fillable = [
        'epobject_id',
        'yes_votes',
        'no_votes',
    ];

    protected $casts = [
        'yes_votes' => 'int',
        'no_votes' => 'int',
    ];

}
