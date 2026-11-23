<?php

/**
 * @file
 * The Laravel application, alongside the legacy pages (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 *
 * www/includes/laravel.php boots it inside legacy pages. Apache sends /user/login to www/docs/laravel.php.
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenAustralia\TWFY\Auth\AuthServiceProvider;
use OpenAustralia\TWFY\Auth\LoginPage;
use OpenAustralia\TWFY\Auth\LoginResponse;

$app = Application::configure(basePath: dirname(__DIR__))
  ->withProviders([AuthServiceProvider::class])
  ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
  ->withMiddleware(function (Middleware $middleware): void {
        // The legacy login form has no token until every legacy form gets one (openaustralia/openaustralia#1069).
        $middleware->validateCsrfTokens(except: ['user/login']);
        $middleware->redirectUsersTo(fn (Request $request): string => LoginResponse::destination($request));
    })
  ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel catches exceptions before the legacy handler sees them, so send them to Sentry from here.
        $exceptions->report(function (Throwable $e): void {
            \Sentry\captureException($e);
        });
        // Draw a failed login as the login page again, with the reasons, as the legacy page did.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->routeIs('login.store')) {
                return response(app(LoginPage::class)->render($e->errors()), $e->status);
            }
        });
    })
  ->create();

// The legacy document root doubles as Laravel's public folder.
$app->usePublicPath(dirname(__DIR__) . '/www/docs');

return $app;
