<?php

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
 * @property string|null $alert_mailed_at When the alert mailer emailed this item. A value means it was already
 *   emailed, so the mailer must not send it again, even if the day is re-imported. Null means not emailed yet.
 * @property bool|null $proof True means the row comes from a proof (draft) Hansard, so the text is NOT final.
 *   False means the Parliament has published the final version. Null means we do not know yet.
 */
class Hansard extends Model {

    protected $table = 'hansard';

    protected $primaryKey = 'epobject_id';

    // Primary key is not auto-increment.
    public $incrementing = false;

    public $timestamps = true;

    // Cast date fields.
    protected $casts = [
        'hdate' => 'date',
        'created' => 'datetime',
        'modified' => 'datetime',
        // A true proof value means the Parliament has not published the final text yet.
        'proof' => 'boolean',
        // Set once the item has been emailed, so the mailer knows not to send it again.
        'alert_mailed_at' => 'datetime',
    ];

    // Fillable fields for mass assignment.
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
        'proof',
        'alert_mailed_at',
    ];

}
