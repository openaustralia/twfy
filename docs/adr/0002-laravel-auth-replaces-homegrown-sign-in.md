# Laravel's auth replaces the homegrown sign-in

twfy signs people in with `THEUSER` (`www/includes/easyparliament/user.php`), code from the 2004 TheyWorkForYou
fork. There are no PHP sessions: the `epuser_id` cookie holds the user id and an md5 of their stored password hash,
and every request reloads that user and recompares the md5. The server can't expire or revoke a sign-in, logging
out only deletes the cookie in one browser, "remember me" lasts 20 years, the cookie has no `Secure`, `HttpOnly` or
`SameSite` flags, and no form carries a CSRF token. twfy#297 fixed a security bug in this code. The app is heading
to Laravel (`illuminate/database` and Eloquent are already in, #863), so this is the first piece of Laravel proper
(openaustralia/openaustralia#1069).

## Considered options

- Harden PHP's native sessions: the smallest change, but throwaway once the app moves to Laravel, and password
  reset, email confirmation and CSRF stay ours to write.
- `illuminate/session` on its own: server-side, revocable sessions from the same family as the ORM, but
  `illuminate/auth` doesn't run outside a Laravel app, so sign-in, reset and confirmation stay ours.
- Laravel's auth (chosen): sessions, "remember me", password reset, email confirmation, login throttling and CSRF,
  all maintained upstream, and the code carries over unchanged when the rest of the app moves.

## Consequences

- A Laravel 13 app lives in the repo alongside the legacy pages, replacing the standalone `illuminate/database` 11.
  Laravel 11 was the first choice, to match it, but its security support has ended and Composer refuses every 11.x
  release for open advisories. Apache sends Laravel's routes (the login page first, through `www/docs/laravel.php`)
  to its front controller, and everything else still runs the legacy PHP.
- `init.php` boots Laravel, so legacy pages read the signed-in user from Laravel's session, and `THEUSER` becomes a
  thin wrapper over `Auth::user()` until the legacy pages are gone.
- Sessions are stored in a database table, so they can be listed and revoked. A session lasts until the browser
  closes, or a day of inactivity, as the old cookie did. Login throttling uses a database cache table too, as Apache
  can't write files into the release directory.
- Forgotten passwords get a reset link that expires after an hour, instead of an emailed new password. Whoever
  knows an email address can no longer lock its owner out by asking for one, and a reset signs the user out
  everywhere.
- Everyone signs in again once when this ships, as the old `epuser_id` cookie is no longer read.
- Existing password hashes come from `password_hash()` with bcrypt, which Laravel verifies as they are, so nobody
  has to reset their password. `users.confirmed` and `registrationtoken` carry across to `email_verified_at`.
- The `epuser_id` cookie code is removed once nothing reads it. Until then, new sign-in work goes in Laravel, not
  `THEUSER`.
- The work ships in steps, one PR each, as listed in openaustralia/openaustralia#1069.
