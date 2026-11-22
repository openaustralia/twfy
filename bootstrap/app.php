<?php

/**
 * @file
 * The Laravel application, alongside the legacy pages (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 *
 * www/includes/laravel.php boots it inside legacy pages. Apache doesn't send any requests to Laravel yet.
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
  ->withMiddleware(function (Middleware $middleware): void {
  })
  ->withExceptions(function (Exceptions $exceptions): void {
  })
  ->create();

// The legacy document root doubles as Laravel's public folder.
$app->usePublicPath(dirname(__DIR__) . '/www/docs');

return $app;
