<?php

/**
 * Phinx configuration.
 *
 * DB connection details come from the same conf/general file the rest of the
 * site uses (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME). They can also be
 * overridden by environment variables of the same names, which is how the
 * test suite already passes them in via the Makefile.
 */

// Load DB_* constants from conf/general if present (DB_* env variables will override regardless)
$confFile = __DIR__ . '/conf/general';
if (is_readable($confFile)) {
    include_once $confFile;
}

$host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
$user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
$pass = getenv('DB_PASSWORD') ?: (defined('DB_PASSWORD') ? DB_PASSWORD : '');
$name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : '');

// Allow "host:port" in DB_HOST (as the Makefile uses for tests).
$port = 3306;
if (strpos($host, ':') !== false) {
    [$host, $port] = explode(':', $host, 2);
    $port = (int) $port;
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'default',
        'default' => [
            'adapter' => 'mysql',
            'host' => $host,
            'name' => $name,
            'user' => $user,
            'pass' => $pass,
            'port' => $port,
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
