<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change to a user's role, made on the admin Users page.
 *
 * @property int $id
 * @property int $user_id The user whose role changed.
 * @property int $changed_by The Superuser who changed it.
 * @property string $from_role
 * @property string $to_role
 * @property string|null $created_at
 * @property-read User|null $user
 * @property-read User|null $changedBy
 */
class UserRoleChange extends Model {

    protected $table = 'user_role_changes';

    /**
     * The database fills created_at, in UTC, as it does for import_errors.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'changed_by',
        'from_role',
        'to_role',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'changed_by' => 'integer',
    ];

    /**
     * The user whose role changed.
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * The Superuser who changed it.
     */
    public function changedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'changed_by', 'user_id');
    }

}
