<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EmailSuppression Eloquent model.
 *
 * @property int $suppression_id
 * @property string $email
 * @property string $reason
 * @property string|null $postal_event
 * @property string|null $reply_excerpt
 * @property string $suppressed_at
 * @property string|null $lifted_at
 */
class EmailSuppression extends Model {

    protected $table = 'email_suppressions';

    protected $primaryKey = 'suppression_id';

    protected $fillable = [
        'email',
        'reason',
        'postal_event',
        'reply_excerpt',
        'suppressed_at',
        'lifted_at',
    ];

}
