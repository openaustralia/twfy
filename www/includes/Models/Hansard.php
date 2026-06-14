<?php

/**
 * @file
 * Eloquent model for hansard table.
 *
 * Vim:sw=4:ts=4:et:nowrap.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Hansard Eloquent model.
 *
 * Maps to the hansard table containing parliamentary speech/debate records.
 *
 * @property int $epobject_id
 * @property string|null $gid
 * @property int $htype
 * @property int $speaker_id
 * @property int $major
 * @property int $section_id
 * @property int $subsection_id
 * @property int $hpos
 * @property string $hdate
 * @property string|null $htime
 * @property string $source_url
 * @property int|null $minor
 * @property string|null $created
 * @property string|null $modified
 */
class Hansard extends Model {

    protected $table = 'hansard';

    protected $primaryKey = 'epobject_id';

    // Primary key is not auto-increment
    public $incrementing = false;

    public $timestamps = false;

    // Cast date fields
    protected $casts = [
        'hdate' => 'date',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'epobject_id',
        'gid',
        'htype',
        'speaker_id',
        'major',
        'section_id',
        'subsection_id',
        'hpos',
        'hdate',
        'htime',
        'source_url',
        'minor',
        'created',
        'modified',
    ];

}
