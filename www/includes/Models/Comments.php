<?php

/**
 * @file
 * Eloquent model for comments table.
 *
 * Vim:sw=4:ts=4:et:nowrap.
 */

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comments Eloquent model.
 *
 * Maps to the comments table containing user comments on hansard sections.
 *
 * @property int $comment_id
 * @property int $user_id
 * @property int $epobject_id
 * @property string|null $body
 * @property string|null $posted
 * @property string|null $modflagged
 * @property bool $visible
 * @property string|null $original_gid
 */
class Comments extends Model {

    protected $table = 'comments';

    protected $primaryKey = 'comment_id';

    public $timestamps = true;

    protected $createdAtColumn = 'posted';

    // Cast date fields
    protected $casts = [
        'posted' => 'datetime',
        'modflagged' => 'datetime',
        'visible' => 'bool',
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'epobject_id',
        'body',
        'posted',
        'modflagged',
        'visible',
        'original_gid',
    ];

}
