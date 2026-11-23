<?php

/**
 * @file
 * Signing in and out as a browser does: through the cookies each request carries. Uses a fictional user.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Starts the next request with nothing left in memory from the last, as it is in a new PHP process.
 */
function cookies_spec_new_request(): void {
    $app = twfy_laravel();
    $app->make('auth')->forgetGuards();
    // The session handler records the user from this guard, so a guard kept from an earlier request names the wrong one.
    $app->forgetInstance('auth.driver');
    $app->make('session')->forgetDrivers();
    $app->forgetInstance('session.store');
    $app->make('cookie')->flushQueuedCookies();
}

/**
 * The cookies a browser keeps after signing in.
 *
 * @return array<string, string>
 *   Cookie values by name.
 */
function cookies_spec_sign_in(bool $remember): array {
    $form = ['email' => 'cora.cookiesspec@example.test', 'password' => 'correct-horse'] + ($remember ? ['remember' => 'true'] : []);
    $response = twfy_laravel()->make(Kernel::class)->handle(Request::create('/user/login', 'POST', $form));
    $cookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    cookies_spec_new_request();

    return $cookies;
}

/**
 * The id of the user a legacy page would see for a request carrying these cookies, or null for nobody.
 *
 * @param array<string, string> $cookies
 *   Cookie values by name.
 */
function cookies_spec_user_for(array $cookies): ?int {
    cookies_spec_new_request();
    $_COOKIE = $cookies;

    return twfy_laravel_user()?->user_id;
}

beforeEach(function () {
    // Laravel's Recaller reads a "remember me" cookie with @unserialize(), which warns on the plain text it falls back to.
    set_error_handler(fn (int $number, string $message): bool => str_contains($message, 'unserialize()'), E_WARNING);
    $this->saved = $_COOKIE;
    $this->user = User::create([
        'firstname' => 'Cora',
        'lastname' => 'Cookiesspec',
        'email' => 'cora.cookiesspec@example.test',
        'password' => password_hash('correct-horse', PASSWORD_DEFAULT),
        'confirmed' => true,
    ]);
    $this->sessionCookie = 'oa_session';
    $this->rememberCookie = 'remember_web_' . sha1(Illuminate\Auth\SessionGuard::class);
});

afterEach(function () {
    restore_error_handler();
    $_COOKIE = $this->saved;
    cookies_spec_new_request();
});

describe('a later request', function () {
    describe('after signing in', function () {
        beforeEach(fn () => $this->cookies = cookies_spec_sign_in(false));

        it('is the user with the session cookie', function () {
            expect(cookies_spec_user_for($this->cookies))->toBe($this->user->user_id);
        });

        it('is nobody without cookies', function () {
            expect(cookies_spec_user_for([]))->toBeNull();
        });
    });

    describe('after signing in with "remember me"', function () {
        beforeEach(fn () => $this->cookies = cookies_spec_sign_in(true));

        it('is the user with every cookie', function () {
            expect(cookies_spec_user_for($this->cookies))->toBe($this->user->user_id);
        });

        it('is the user with only the "remember me" cookie', function () {
            expect(cookies_spec_user_for([$this->rememberCookie => $this->cookies[$this->rememberCookie]]))->toBe($this->user->user_id);
        });
    });

    describe('after signing out', function () {
        beforeEach(function () {
            $this->cookies = cookies_spec_sign_in(true);
            cookies_spec_user_for($this->cookies);
            (new THEUSER())->logout('/safe-path');
        });

        it('is nobody with the old session cookie', function () {
            expect(cookies_spec_user_for([$this->sessionCookie => $this->cookies[$this->sessionCookie]]))->toBeNull();
        });

        it('is nobody with the old "remember me" cookie', function () {
            expect(cookies_spec_user_for([$this->rememberCookie => $this->cookies[$this->rememberCookie]]))->toBeNull();
        });
    });

    describe('after the password changes elsewhere', function () {
        it('is nobody with the old cookies', function () {
            $cookies = cookies_spec_sign_in(true);
            $this->user->signOutEverywhere();

            expect(cookies_spec_user_for($cookies))->toBeNull();
        });
    });
});
