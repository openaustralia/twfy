<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Sets the new password when someone follows the link in their reset email.
 */
class ResetUserPassword implements ResetsUserPasswords {

    /**
     * Saves a new password, and signs the user out everywhere, as whoever knew the old one may still be signed in.
     *
     * @param \OpenAustralia\TWFY\Models\User $user
     *   The user.
     * @param array<string, mixed> $input
     *   The form fields.
     */
    public function reset($user, array $input): void {
        Validator::make($input, [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ], [
            'password.required' => 'Please enter your new password',
        ])->validate();

        $user->changePassword($input['password']);
    }

}
