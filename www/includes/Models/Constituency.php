<?php

/**
 * @file
 * Eloquent model for constituency table.
 *
 * Vim:sw=4:ts=4:et:nowrap.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Constituency Eloquent model.
 *
 * Maps to the constituency table containing parliamentary constituency information.
 *
 * @property string $name
 * @property bool $main_name
 * @property string $from_date
 * @property string $to_date
 * @property int|null $cons_id
 */
class Constituency extends Model {

    protected $table = 'constituency';

    public $timestamps = true;

    // Cast date fields
    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'main_name' => 'bool',
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'main_name',
        'from_date',
        'to_date',
        'cons_id',
    ];

}
