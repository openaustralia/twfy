<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PbcMember Eloquent model.
 *
 * @property int $id
 * @property int $member_id
 * @property int $chairman
 * @property int $bill_id
 * @property string $sitting
 * @property int $attending
 */
class PbcMember extends Model {

    protected $table = 'pbc_members';

    protected $fillable = [
        'member_id',
        'chairman',
        'bill_id',
        'sitting',
        'attending',
    ];

    protected $casts = [
        'chairman' => 'bool',
        'attending' => 'bool',
    ];

}
