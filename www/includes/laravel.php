<?php

/**
 * @file
 * Boots the Laravel application inside a legacy page or script, once.
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;

/**
 * The booted Laravel application.
 *
 * It skips two of Laravel's steps: HandleExceptions, so the legacy error handler and Sentry stay in charge, and
 * LoadEnvironmentVariables, as settings come from conf/general and the root .env is for Docker Compose. It uses the
 * Eloquent connection eloquent.php made, so legacy queries and Laravel share one connection and its transactions.
 */
function twfy_laravel(): Application {
    static $app = null;
    if ($app === null) {
        $app = require __DIR__ . '/../../bootstrap/app.php';
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
