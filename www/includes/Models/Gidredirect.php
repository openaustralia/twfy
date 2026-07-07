<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gidredirect Eloquent model.
 *
 * @property string|null $gid_from
 * @property string|null $gid_to
 * @property string $hdate
 * @property int|null $major
 */
class Gidredirect extends Model {

    protected $table = 'gidredirect';

    public $incrementing = false;

    protected $primaryKey = 'gid_from';

    protected $keyType = 'string';

    protected $fillable = [
        'gid_from',
        'gid_to',
        'hdate',
        'major',
    ];

    protected $casts = [
        'hdate' => 'date',
    ];

    public function fromHansard(): BelongsTo {
        return $this->belongsTo(Hansard::class, 'gid_from', 'gid');
    }

    public function toHansard(): BelongsTo {
        return $this->belongsTo(Hansard::class, 'gid_to', 'gid');
    }

}
