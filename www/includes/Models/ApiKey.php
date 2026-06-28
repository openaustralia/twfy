<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

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

}
