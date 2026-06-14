<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PostcodeLookup Eloquent model.
 *
 * Maps to the postcode_lookup table containing postcode to constituency mappings.
 *
 * @property string $postcode
 * @property string $name
 */
class PostcodeLookup extends Model {

    protected $table = 'postcode_lookup';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'postcode',
        'name',
    ];

}
