<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Eloquent model for the `users` table.
 *
 * The primary key is `user_id` (the legacy schema predates Eloquent's `id`
 * convention). `created_at` / `updated_at` are managed by Eloquent in the
 * usual way; legacy `registrationtime` and `lastvisit` columns remain for
 * compatibility with existing code paths.
 */
class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

    use Authenticatable;
    use CanResetPassword;

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
     * Signs the user out everywhere, as whoever knew their old password may still be signed in.
     *
     * Replaces the "remember me" token, which signs out every browser that kept one, and deletes the user's sessions.
     *
     * @param string|null $exceptSessionId
     *   A session to keep, such as the one the user is changing their password in.
     */
    public function signOutEverywhere(?string $exceptSessionId = null): void {
        static::whereKey($this->getKey())->update(['remember_token' => Str::random(60)]);

        Session::where('user_id', $this->getKey())
          ->when($exceptSessionId, fn (Builder $query) => $query->where('id', '!=', $exceptSessionId))
          ->delete();
    }

    /**
     * Emails the link for choosing a new password, through the site's own mail sending.
     *
     * Deleted users get nothing, as they can't sign in.
     *
     * @param string $token
     *   The reset token.
     */
    public function sendPasswordResetNotification($token): void {
        if ($this->deleted) {
            return;
        }

        send_template_email(
            ['to' => $this->email, 'template' => 'password_reset'],
            [
                'EMAIL' => $this->email,
                'MINUTES' => (string) config('auth.passwords.users.expire'),
                'RESETURL' => route('password.reset', ['token' => $token, 'email' => $this->email]),
            ]
        );
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
        'remember_token',
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
