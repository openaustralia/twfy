<?php

/**
 * @file
 * Eloquent model for uservotes table.
 *
 * Vim:sw=4:ts=4:et:nowrap.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uservotes Eloquent model.
 *
 * Maps to the uservotes table containing user votes/ratings on hansard sections.
 *
 * @property int $user_id
 * @property int $epobject_id
 * @property int $vote
 */
class Uservotes extends Model {

    protected $table = 'uservotes';

    // No primary key - this is a bridge/junction table
    public $timestamps = true;
    public $incrementing = false;

    // Cast vote as boolean-ish (0 or 1)
    protected $casts = [
        'vote' => 'int',
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'epobject_id',
        'vote',
    ];

}
