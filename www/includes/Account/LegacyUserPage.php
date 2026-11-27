<?php

namespace OpenAustralia\TWFY\Account;

/**
 * Where the old /user/ address sends someone whose own details it once showed.
 *
 * Joining and an administrator editing another user are still at /user/. Nobody's details are public.
 */
class LegacyUserPage {

    /**
     * The path, under the site's root, of the account page that replaced what the "pg" variable asked for.
     */
    public static function destination(string $pg): string {
        return in_array($pg, ['edit', 'editother'], true) ? 'account/edit/' : 'account/';
    }

}
