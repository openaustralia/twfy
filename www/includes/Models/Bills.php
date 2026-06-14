<?php

/**
 * @file
 * Eloquent model for bills table.
 *
 * Vim:sw=4:ts=4:et:nowrap.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bills Eloquent model.
 *
 * Maps to the bills table containing parliamentary bill/legislation information.
 *
 * @property int $id
 * @property string $title
 * @property string $url
 * @property bool $lords
 * @property string $session
 * @property string $standingprefix
 */
class Bills extends Model {

    protected $table = 'bills';

    protected $primaryKey = 'id';

    public $timestamps = false;

    // Cast boolean fields
    protected $casts = [
        'lords' => 'bool',
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'title',
        'url',
        'lords',
        'session',
        'standingprefix',
    ];

}
