<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenAustralia\TWFY\Models\User;

/**
 * Finds the user for an email and password, for Fortify's login.
 *
 * Passwords are bcrypt from password_hash(), or md5-crypt ($1$) from before PHP 8, which this upgrades to bcrypt
 * when someone signs in. Laravel's own hasher throws on md5-crypt, so Fortify can't check these itself.
 */
class Authenticator {

    /**
     * Finds the user for a login request.
     *
     * @return \OpenAustralia\TWFY\Models\User|null
     *   The user, or null if the email and password don't match a user who can sign in.
     */
    public function __invoke(Request $request): ?User {
        return $this->authenticate((string) $request->input('email'), (string) $request->input('password'));
    }

    /**
     * Finds the user for an email and password.
     *
     * @return \OpenAustralia\TWFY\Models\User|null
     *   The user, or null if the email and password don't match a user who can sign in.
     *
     * @throws \Illuminate\Validation\ValidationException
     *   When the password is right but the user hasn't confirmed their email address.
     */
    public function authenticate(string $email, string $password): ?User {
        $user = User::where('email', $email)->first();

        if (!$user || $user->deleted || !$this->passwordMatches($user, $password)) {
            return null;
        }

        if (!$user->confirmed) {
            throw ValidationException::withMessages([
                'email' => ["You haven't confirmed your email address yet. Please follow the link in the email we sent you."],
            ]);
        }

        return $user;
    }

    /**
     * Whether the password is the user's, upgrading an md5-crypt hash to bcrypt when it is.
     */
    private function passwordMatches(User $user, string $password): bool {
        $stored = (string) $user->password;

        if (!str_starts_with($stored, '$1$')) {
            return password_verify($password, $stored);
        }

        // FIXME: remove once count is zero: `select count(*) from users where password like '$1$%';`.
        if (!hash_equals($stored, crypt($password, $stored))) {
            return false;
        }

        $user->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);

        return true;
    }

}
