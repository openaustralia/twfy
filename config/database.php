<?php

/**
 * @file
 * Database settings for artisan. Web requests reuse eloquent.php's connection instead.
 */

$host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
$port = '3306';
if (str_contains($host, ':')) {
    [$host, $port] = explode(':', $host, 2);
}

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : ''),
            'username' => getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : ''),
            'password' => getenv('DB_PASSWORD') ?: (defined('DB_PASSWORD') ? DB_PASSWORD : ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_0900_ai_ci',
            'prefix' => '',
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
    ],
];
