<?php

/**
 * Stub functions that mysql.php depends on at include-time and at runtime.
 * These are replaced by real implementations in the live application.
 */
function twfy_debug(string $type, string $msg): void {}

function getmicrotime(): float {
    return microtime(true);
}

function get_cookie_var(string $varname): string {
    return $_COOKIE[$varname] ?? '';
}

if (!defined('CONSTITUENCY_COOKIE')) {
    define('CONSTITUENCY_COOKIE', 'constituency');
}

if (!defined('COOKIEDOMAIN')) {
    define('COOKIEDOMAIN', '');
}

require_once __DIR__ . '/../www/includes/mysql.php';

/**
 * Test-safe DB wrapper used by USER/THEUSER classes.
 */
class ParlDB extends MySQL {
    public function __construct() {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');
        $name = getenv('DB_NAME');

        if (!$host || $user === false || $name === false) {
            return;
        }

        global $global_connection;

        if (!$global_connection) {
            try {
                $conn = @mysqli_connect($host, $user, $pass ?: '', $name);
            } catch (mysqli_sql_exception $e) {
                $conn = false;
            }

            if (!$conn) {
                return;
            }

            $global_connection = $conn;
        }

        try {
            $prop = new ReflectionProperty(MySQL::class, 'conn');
            $prop->setAccessible(true);
            $prop->setValue($this, $global_connection);
        } catch (ReflectionException $e) {
            return;
        }
    }
}

require_once __DIR__ . '/../www/includes/easyparliament/user.php';

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

    global $global_connection;

    if (!$global_connection) {
        // Use mysqli directly to avoid exit() on error
        try {
            $conn = @mysqli_connect($host, $user, $pass ?: '', $name);
        } catch (mysqli_sql_exception $e) {
            $conn = false;
        }

        if (!$conn) {
            return null;
        }

        $global_connection = $conn;
    } else {
        $conn = $global_connection;
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




