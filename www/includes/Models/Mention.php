<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mention Eloquent model.
 *
 * @property int $mention_id
 * @property string|null $gid
 * @property int $type
 * @property string|null $date
 * @property string|null $url
 * @property string|null $mentioned_gid
 */
class Mention extends Model {

    protected $table = 'mentions';

    protected $primaryKey = 'mention_id';

    protected $fillable = [
        'gid',
        'type',
        'date',
        'url',
        'mentioned_gid',
    ];

    protected $casts = [
        'date' => 'date',
    ];

}
