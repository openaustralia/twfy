<?php

/**
 * @file
 * Requests to the account pages from a browser Laravel has signed in. Uses fictional users.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sessions and requests for the account page specs.
 */
final class AccountRequests {

    /**
     * Signs a user in: saves a session for them, in the test's transaction.
     *
     * @return array{id: string, token: string, cookies: array<string, string>}
     *   The session's id, its CSRF token, and the encrypted cookie that names it.
     */
    public static function signIn(User $user): array {
        $app = twfy_laravel();
        $encrypter = $app->make('encrypter');
        $name = $app->make('config')->get('session.cookie');
        $id = bin2hex(random_bytes(20));
        $token = bin2hex(random_bytes(20));
        Session::create([
            'id' => $id,
            'user_id' => $user->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'account page spec',
            'payload' => base64_encode(serialize(['_token' => $token, twfy_laravel_guard()->getName() => $user->user_id])),
            'last_activity' => time(),
        ]);

        return ['id' => $id, 'token' => $token, 'cookies' => [$name => $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()) . $id, false)]];
    }

    /**
     * Sends a request as one browser, with its CSRF token, or as a visitor who isn't signed in.
     *
     * @param array<string, string> $input
     *   Form fields.
     * @param array{id: string, token: string, cookies: array<string, string>}|null $session
     *   The browser's session from signIn(), or null for a signed-out visitor.
     * @param bool $withToken
     *   Whether to send the session's CSRF token, which only forms need.
     */
    public static function send(string $method, string $uri, array $input = [], ?array $session = null, bool $withToken = true): Response {
        laravel_spec_new_request();
        if ($session && $withToken && $method !== 'GET') {
            $input += ['_token' => $session['token']];
        }

        return twfy_laravel()->make(Kernel::class)->handle(Request::create($uri, $method, $input, $session['cookies'] ?? []));
    }

}
