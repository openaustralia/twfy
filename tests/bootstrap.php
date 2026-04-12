<?php

/**
 * Stub functions that mysql.php depends on at include-time and at runtime.
 * These are replaced by real implementations in the live application.
 */
function twfy_debug(string $type, string $msg): void {}

function getmicrotime(): float {
    return microtime(true);
}

require_once __DIR__ . '/../www/includes/mysql.php';

/**
 * Wrapper that creates a database connection for tests without calling exit().
 * Returns a MySQL instance if successful, null otherwise.
 */
function getTestDatabase(): ?MySQL {
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $name = getenv('DB_NAME');

    if (!$host || $user === false || $name === false) {
        return null;
    }

    // Use mysqli directly to avoid exit() on error
    $conn = @mysqli_connect($host, $user, $pass ?: '', $name);
    if (!$conn) {
        return null;
    }

    // Create a MySQL instance that uses this connection
    $db = new MySQL();

    // Use reflection to set the private $conn property
    try {
        $prop = new ReflectionProperty($db, 'conn');
        $prop->setAccessible(true);
        $prop->setValue($db, $conn);
        return $db;
    } catch (ReflectionException $e) {
        return null;
    }
}

/**
 * Static helper for integration tests to get a connection.
 */
class TestDatabase {
    public static function tryConnect(): ?MySQL {
        return getTestDatabase();
    }
}




