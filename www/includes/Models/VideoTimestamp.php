<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * VideoTimestamp Eloquent model.
 *
 * @property int $id
 * @property string $gid
 * @property int|null $user_id
 * @property string $atime
 * @property int $deleted
 * @property string $whenstamped
 */
class VideoTimestamp extends Model {

    protected $table = 'video_timestamps';

    protected $fillable = [
        'gid',
        'user_id',
        'atime',
        'deleted',
    ];

    protected $casts = [
        'deleted' => 'bool',
    ];

}
