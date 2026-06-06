<?php

/**
 * @file
 * Bootstraps Eloquent (illuminate/database) as a standalone "Capsule" so the
 * existing legacy code (which uses the ParlDB / MySQL wrapper) can opt in to
 * Eloquent models without a full Laravel install.
 *
 * Safe to include more than once: the manager is only created and booted the
 * first time. Relies on the DB_* constants defined in conf/general.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

if (!class_exists(Capsule::class)) {
    // Composer dependencies not installed yet; nothing to do.
    return;
}

if (!isset($GLOBALS['twfy_eloquent_booted'])) {
    $capsule = new Capsule();

    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => DB_HOST,
        'database'  => DB_NAME,
        'username'  => DB_USER,
        'password'  => DB_PASSWORD,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
        'prefix'    => '',
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $GLOBALS['twfy_eloquent_booted'] = true;
}
