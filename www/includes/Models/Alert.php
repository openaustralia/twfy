<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Alert Eloquent model.
 *
 * @property int $alert_id
 * @property string $email
 * @property string $criteria
 * @property int $deleted
 * @property string $registrationtoken
 * @property int $confirmed
 * @property string $created
 * @property int $recommended
 */
class Alert extends Model {

    protected $table = 'alerts';

    protected $primaryKey = 'alert_id';

    protected $fillable = [
        'email',
        'criteria',
        'deleted',
        'registrationtoken',
        'confirmed',
        'created',
        'recommended',
    ];

    protected $casts = [
        'deleted' => 'bool',
        'confirmed' => 'bool',
        'recommended' => 'bool',
    ];

}
