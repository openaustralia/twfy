<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Eloquent model for the `users` table.
 *
 * `pending_email` is an address someone asked to change to, until they follow the link emailed to it.
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
     * The hash to store for a password.
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
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
     * Whether another user already has an address.
     *
     * Deleted and unconfirmed users count, as joining refuses their addresses too.
     *
     * @param string $email
     *   The address.
     * @param int|null $exceptUserId
     *   A user whose own address doesn't count.
     */
    public static function emailTaken(string $email, ?int $exceptUserId = null): bool {
        return static::where('email', $email)
          ->when($exceptUserId, fn (Builder $query) => $query->where('user_id', '!=', $exceptUserId))
          ->exists();
    }

    /**
     * Emails the user from one of the templates, through the site's own mail sending.
     *
     * Deleted users get nothing, as they can't sign in. {FIRSTNAME}, {EMAIL} and {CONTACTEMAIL} are always filled in.
     *
     * @param string $template
     *   The template's name, in easyparliament/templates/emails.
     * @param array<string, string> $merge
     *   What to fill the template's other {TOKENS} with.
     * @param string|null $to
     *   An address to send to instead of the user's own, such as one waiting to be confirmed.
     */
    public function sendTemplate(string $template, array $merge = [], ?string $to = null): void {
        if ($this->deleted) {
            return;
        }

        send_template_email(
            ['to' => $to ?? $this->email, 'template' => $template],
            // The greeting uses the first name, or the last name for someone who gave only that.
            $merge + ['FIRSTNAME' => trim((string) $this->firstname) !== '' ? $this->firstname : $this->lastname, 'EMAIL' => $this->email, 'CONTACTEMAIL' => CONTACTEMAIL]
        );
    }

    /**
     * Emails the link for choosing a new password.
     *
     * @param string $token
     *   The reset token.
     */
    public function sendPasswordResetNotification($token): void {
        $this->sendTemplate('password_reset', [
            'MINUTES' => (string) config('auth.passwords.users.expire'),
            'RESETURL' => route('password.reset', ['token' => $token, 'email' => $this->email]),
        ]);
    }

    /**
     * The fewest characters in a password, which Laravel's own default rule also sets (see AuthServiceProvider).
     */
    public const PASSWORD_MIN = 8;

    /**
     * The most characters in a password. bcrypt ignores everything past 72 bytes, so a longer one would only look
     * stronger than it is.
     */
    public const PASSWORD_MAX = 72;

    /**
     * Why a new password is not allowed, or null if it is.
     *
     * Applies Laravel's password rule, so the legacy pages follow the same rule as the Laravel ones.
     */
    public static function passwordProblem(string $password): ?string {
        $validator = Validator::make(['password' => $password], ['password' => [Password::defaults()]], ['password.min' => 'Please enter at least :min characters', 'password.max' => 'Please enter at most :max characters']);

        return $validator->fails() ? $validator->errors()->first('password') : null;
    }

    /**
     * The first and last names joined, which copes with either being empty as people may give just one.
     */
    public function fullName(): string {
        return trim(implode(' ', array_filter([trim((string) $this->firstname), trim((string) $this->lastname)], fn (string $part): bool => $part !== '')));
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
        'pending_email',
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
