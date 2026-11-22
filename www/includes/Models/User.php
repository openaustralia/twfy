<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `users` table.
 *
 * The primary key is `user_id` (the legacy schema predates Eloquent's `id`
 * convention). `created_at` / `updated_at` are managed by Eloquent in the
 * usual way; legacy `registrationtime` and `lastvisit` columns remain for
 * compatibility with existing code paths.
 */
class User extends Model {

    /**
     * The roles a user can have (users.status), lowest first. Administrator and Superuser can open the admin pages.
     */
    public const ROLES = ['Viewer', 'User', 'Moderator', 'Administrator', 'Superuser'];

    /**
     * Only users with a role, or everyone when the role is null or not one of ROLES.
     *
     * @param \Illuminate\Database\Eloquent\Builder<User> $query
     *   The query.
     * @param string|null $role
     *   The role.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     *   The query.
     */
    public function scopeWithRole(Builder $query, ?string $role): Builder {
        return in_array($role, self::ROLES, true) ? $query->where('status', $role) : $query;
    }

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'lastvisit',
        'registrationtime',
        'registrationip',
        'status',
        'emailpublic',
        'optin',
        'deleted',
        'constituency',
        'registrationtoken',
        'confirmed',
        'url',
        'api_key',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'registrationtoken',
        'api_key',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'lastvisit'        => 'datetime',
        'registrationtime' => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'emailpublic'      => 'bool',
        'optin'            => 'bool',
        'deleted'          => 'bool',
        'confirmed'        => 'bool',
    ];

}
