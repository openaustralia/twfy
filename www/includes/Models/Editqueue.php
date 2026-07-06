<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Editqueue Eloquent model.
 *
 * @property int $edit_id
 * @property int|null $user_id
 * @property int|null $edit_type
 * @property int|null $epobject_id_l
 * @property int|null $epobject_id_h
 * @property int|null $glossary_id
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string|null $title
 * @property string|null $body
 * @property string|null $submitted
 * @property int|null $editor_id
 * @property int|null $approved
 * @property string|null $decided
 * @property string|null $reason
 */
class Editqueue extends Model {

    protected $table = 'editqueue';

    protected $primaryKey = 'edit_id';

    protected $fillable = [
        'user_id',
        'edit_type',
        'epobject_id_l',
        'epobject_id_h',
        'glossary_id',
        'time_start',
        'time_end',
        'title',
        'body',
        'submitted',
        'editor_id',
        'approved',
        'decided',
        'reason',
    ];

    protected $casts = [
        'approved' => 'bool',
    ];

}
