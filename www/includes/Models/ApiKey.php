<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiKey Eloquent model.
 *
 * @property int $id
 * @property int $user_id
 * @property string $api_key
 * @property int $commercial
 * @property string $created
 * @property string|null $disabled
 * @property string $reason
 */
class ApiKey extends Model {

    protected $table = 'api_key';

    protected $fillable = [
        'user_id',
        'api_key',
        'commercial',
        'created',
        'disabled',
        'reason',
    ];

    protected $casts = [
        'commercial' => 'bool',
    ];

    /**
     * The person the key belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ApiKey>
     *   The relationship.
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

}
