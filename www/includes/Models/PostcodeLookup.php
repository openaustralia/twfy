<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * The electorates a postcode falls in.
     *
     * @return \Illuminate\Support\Collection<int, string>
     *   Their names, each once.
     */
    public static function electoratesFor(string $postcode): Collection {
        return static::query()->where('postcode', $postcode)->orderBy('name')->pluck('name')->unique()->values();
    }

}
