<?php

namespace OpenAustralia\TWFY\Models;

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
