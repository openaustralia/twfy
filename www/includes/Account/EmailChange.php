<?php

namespace OpenAustralia\TWFY\Account;

use Illuminate\Support\Facades\URL;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\User;

/**
 * Changing the email address on an account, once the owner of the new address has followed a link emailed to it.
 *
 * Until then the account keeps its address, and the new one waits in users.pending_email. Asking again replaces the
 * waiting address, which stops the links sent for the old one working, as each link carries a hash of its address.
 */
class EmailChange {

    /**
     * How long a link works for.
     */
    public const MINUTES = 60;

    /**
     * Starts a change: remembers the new address, emails it a link, and tells the current address.
     *
     * @param \OpenAustralia\TWFY\Models\User $user
     *   The user.
     * @param string $email
     *   The new address, already checked.
     */
    public function request(User $user, string $email): void {
        $user->forceFill(['pending_email' => $email])->save();

        $this->sendLink($user);
        $user->sendTemplate('email_change_notice', ['NEWEMAIL' => $email, 'ACCOUNTURL' => route('account.show')]);
    }

    /**
     * Emails the link to the waiting address again, if there is one.
     */
    public function resend(User $user): void {
        if ($user->pending_email !== null) {
            $this->sendLink($user);
        }
    }

    /**
     * Drops the waiting address, which stops its links working.
     */
    public function cancel(User $user): void {
        $user->forceFill(['pending_email' => null])->save();
    }

    /**
     * The link that confirms the waiting address.
     *
     * It is signed, so it can't be altered or made up, and expires. It names the user, and holds a hash of the
     * address it is for.
     */
    public function confirmationUrl(User $user): string {
        return URL::temporarySignedRoute('account.email.confirm', now()->addMinutes(self::MINUTES), [
            'user' => $user->getKey(),
            'hash' => $this->hash((string) $user->pending_email),
        ]);
    }

    /**
     * Makes the waiting address the account's, if the link's hash is still for it and nobody else has taken it.
     *
     * Moves the user's alerts to the new address, signs out every other browser, and tells the old address.
     *
     * @param \OpenAustralia\TWFY\Models\User $user
     *   The user.
     * @param string $hash
     *   The hash from the link.
     * @param string|null $keepSessionId
     *   The session to keep signed in, which is the one confirming.
     *
     * @return bool
     *   Whether the address changed.
     */
    public function confirm(User $user, string $hash, ?string $keepSessionId = null): bool {
        $old = $user->getConnection()->transaction(function () use ($user, $hash): ?string {
            // Locked, so two clicks on the link can't both move the alerts.
            $current = User::whereKey($user->getKey())->lockForUpdate()->first();
            $new = $current?->pending_email;

            if ($new === null || !hash_equals($this->hash($new), $hash)) {
                return null;
            }

            if (User::emailTaken($new, $current->getKey())) {
                // Someone joined with it after the change was asked for.
                $current->forceFill(['pending_email' => null])->save();
                return null;
            }

            $old = $current->email;
            Alert::where('email', $old)->update(['email' => $new]);
            $current->forceFill(['email' => $new, 'pending_email' => null])->save();

            return $old;
        });

        if ($old === null) {
            return false;
        }

        $user->refresh();
        $user->signOutEverywhere($keepSessionId);
        $user->sendTemplate('email_changed_notice', ['EMAIL' => $old, 'NEWEMAIL' => $user->email], $old);

        return true;
    }

    /**
     * Emails the confirmation link to the waiting address.
     */
    private function sendLink(User $user): void {
        $user->sendTemplate('email_change_confirm', [
            'NEWEMAIL' => (string) $user->pending_email,
            'MINUTES' => (string) self::MINUTES,
            'CONFIRMURL' => $this->confirmationUrl($user),
        ], $user->pending_email);
    }

    /**
     * What a link holds in place of the address it is for.
     */
    private function hash(string $email): string {
        return hash('sha256', strtolower($email));
    }

}
