<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Memberinfo Eloquent model.
 *
 * @property int $member_id
 * @property string $data_key
 * @property string $data_value
 * @property string $lastupdate
 */
class Memberinfo extends Model {

    // Eloquent does not natively support composite primary keys, so this model
    // keeps a manual save-query override alongside the schema-level composite PK.
    private const COMPOSITE_PRIMARY_KEY = ['member_id', 'data_key'];

    protected $table = 'memberinfo';

    public $incrementing = false;

    protected $primaryKey = self::COMPOSITE_PRIMARY_KEY;

    protected $fillable = [
        'member_id',
        'data_key',
        'data_value',
    ];

    /**
     * Eloquent does not natively support composite keys, so updates need the
     * full key pair applied manually.
     */
    protected function setKeysForSaveQuery($query): Builder {
        foreach (self::COMPOSITE_PRIMARY_KEY as $keyName) {
            $query->where($keyName, '=', $this->getAttribute($keyName));
        }

        return $query;
    }

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

}
