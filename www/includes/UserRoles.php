<?php

namespace OpenAustralia\TWFY;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;

/**
 * Listing users with their roles, and changing a role, for the admin Users page.
 */
class UserRoles {

    public const PER_PAGE = 100;

    public const CHANGED = 'changed';

    public const UNCHANGED = 'unchanged';

    public const FORBIDDEN = 'forbidden';

    public const OWN_ROLE = 'own';

    public const BAD_TOKEN = 'token';

    public const INVALID = 'invalid';

    /**
     * One page of users, ordered by name.
     *
     * @param int $page
     *   The page, from 1.
     * @param string|null $role
     *   Only users with this role, or everyone when null.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     *   The users.
     */
    public static function page(int $page, ?string $role = null): Collection {
        return User::withRole($role)
          ->orderBy('lastname')->orderBy('firstname')->orderBy('user_id')
          ->forPage(max(1, $page), self::PER_PAGE)
          ->get(['user_id', 'firstname', 'lastname', 'email', 'status', 'confirmed', 'deleted']);
    }

    /**
     * The number of pages of users.
     *
     * @param string|null $role
     *   Only users with this role, or everyone when null.
     */
    public static function pages(?string $role = null): int {
        return max(1, (int) ceil(User::withRole($role)->count() / self::PER_PAGE));
    }

    /**
     * The most recent role changes, newest first, with both users.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, UserRoleChange>
     *   The changes.
     */
    public static function recentChanges(int $limit = 20): Collection {
        return UserRoleChange::with(['user:user_id,firstname,lastname', 'changedBy:user_id,firstname,lastname'])
          ->orderByDesc('id')
          ->limit($limit)
          ->get();
    }

    /**
     * The token a role change form carries, so another site can't post one for a signed-in Superuser.
     *
     * It is keyed by the Superuser's stored password hash, so it needs no new secret and changes with their password.
     * The login cookie holds an md5 of that hash, not the hash, so the cookie can't be used to make one.
     */
    public static function token(int $user_id, string $password_hash): string {
        return hash_hmac('sha256', 'user-roles:' . $user_id, $password_hash);
    }

    /**
     * Changes a user's role, and records the change.
     *
     * @param int $actor_id
     *   The signed-in user making the change.
     * @param string $actor_role
     *   Their role. Only a Superuser can change roles.
     * @param string $actor_password_hash
     *   Their stored password hash, to check the token against.
     * @param string $token
     *   The token from the form.
     * @param int $user_id
     *   The user whose role changes.
     * @param string $role
     *   The new role, one of User::ROLES.
     *
     * @return string
     *   One of the result constants.
     */
    public static function change(int $actor_id, string $actor_role, string $actor_password_hash, string $token, int $user_id, string $role): string {
        // With no hash to key it, anyone could make the token.
        if ($actor_password_hash === '' || !hash_equals(self::token($actor_id, $actor_password_hash), $token)) {
            return self::BAD_TOKEN;
        }
        if ($actor_role !== 'Superuser') {
            return self::FORBIDDEN;
        }
        if ($user_id === $actor_id) {
            return self::OWN_ROLE;
        }
        $user = User::find($user_id);
        if ($user === null || !in_array($role, User::ROLES, true)) {
            return self::INVALID;
        }
        $from = (string) $user->status;
        if ($from === $role) {
            return self::UNCHANGED;
        }
        DB::connection()->transaction(function () use ($user, $actor_id, $from, $role) {
            $user->status = $role;
            $user->save();
            UserRoleChange::create(['user_id' => $user->user_id, 'changed_by' => $actor_id, 'from_role' => $from, 'to_role' => $role]);
        });
        return self::CHANGED;
    }

}
