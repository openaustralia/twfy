<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `moffice` table.
 *
 * The primary key is `moffice_id`. This is a legacy table without Eloquent's
 * managed timestamp columns.
 */
class Moffice extends Model {

    /**
     * @var string
     */
    protected $table = 'moffice';

    /**
     * @var string
     */
    protected $primaryKey = 'moffice_id';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dept',
        'position',
        'from_date',
        'to_date',
        'person',
        'source',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'moffice_id' => 'int',
        'person'     => 'int',
        'from_date'  => 'date',
        'to_date'    => 'date',
    ];

}
