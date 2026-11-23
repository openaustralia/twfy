<?php

/**
 * @file
 * Boots the Laravel application inside a legacy page or script, once.
 */

use Illuminate\Auth\SessionGuard;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Store;
use OpenAustralia\TWFY\Models\User;

/**
 * The booted Laravel application.
 *
 * It skips two of Laravel's steps: HandleExceptions, so the legacy error handler and Sentry stay in charge, and
 * LoadEnvironmentVariables, as settings come from conf/general and the root .env is for Docker Compose. It uses the
 * Eloquent connection eloquent.php made, so Eloquent queries and Laravel share one connection and its transactions.
 */
function twfy_laravel(): Application {
    static $app = null;
    if ($app === null) {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        // The auth guard needs a request, and Laravel's kernel only makes one for its own requests.
        $app->instance('request', Request::capture());
        if (isset($GLOBALS['twfy_capsule'])) {
            $db = $GLOBALS['twfy_capsule']->getDatabaseManager();
            $app->afterBootstrapping(RegisterProviders::class, fn (Application $app) => $app->instance('db', $db));
        }
        $app->bootstrapWith([
            LoadConfiguration::class,
            RegisterFacades::class,
            RegisterProviders::class,
            BootProviders::class,
        ]);
    }
    return $app;
}

/**
 * The guard that signs people in.
 */
function twfy_laravel_guard(): SessionGuard {
    return twfy_laravel()->make('auth')->guard();
}

/**
 * Laravel's session store.
 */
function twfy_laravel_session(): Store {
    return twfy_laravel()->make('session.store');
}

/**
 * Runs a callback with Laravel's session started, as for one of its own requests, then sends the cookies it set.
 *
 * Legacy pages call this to sign people in and out, as their own requests never pass through Laravel's middleware.
 * It sends headers, so call it before any output.
 *
 * @template T
 *
 * @param callable(): T $callback
 *   What to run once the session is started.
 *
 * @return T
 *   What the callback returned.
 */
function twfy_laravel_with_session(callable $callback): mixed {
    $app = twfy_laravel();
    $result = null;

    $response = (new Pipeline($app))
      ->send(Request::capture())
      ->through([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class])
      ->then(function (Request $request) use ($app, $callback, &$result): Response {
            $app->instance('request', $request);
            // Saving ages the flash data, so keep what Laravel's own request is about to show.
            twfy_laravel_session()->reflash();
            $result = $callback();
            return new Response();
      });

    foreach ($response->headers->getCookies() as $cookie) {
        header('Set-Cookie: ' . $cookie, false);
    }

    return $result;
}

/**
 * The user Laravel has signed in for this request, from the session cookie or the "remember me" cookie.
 *
 * Visitors with neither cookie are anonymous without a session being started for them.
 */
function twfy_laravel_user(): ?User {
    $guard = twfy_laravel_guard();

    if (!$guard->hasUser()) {
        $config = twfy_laravel()->make('config');
        if (!isset($_COOKIE[$config->get('session.cookie')]) && !isset($_COOKIE[$guard->getRecallerName()])) {
            return null;
        }
        return twfy_laravel_with_session(fn () => $guard->user());
    }

    return $guard->user();
}
