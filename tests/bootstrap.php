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

// Define file paths for include_once statements
if (!defined('BASEDIR')) {
    define('BASEDIR', __DIR__ . '/../www');
}

if (!defined('INCLUDESPATH')) {
    define('INCLUDESPATH', __DIR__ . '/../www/includes/');
}

if (!defined('EASYPARLIAMENTPATH')) {
    define('EASYPARLIAMENTPATH', __DIR__ . '/../www/includes/easyparliament/');
}

require_once __DIR__ . '/../www/includes/mysql.php';

/**
 * @return array{host:string,user:string,pass:string,name:string}|null
 */
function getTestDbConfig(): ?array {
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $name = getenv('DB_NAME');

    if (!$host || $user === false || $name === false) {
        return null;
    }

    return [
        'host' => $host,
        'user' => $user,
        'pass' => $pass === false ? '' : $pass,
        'name' => $name,
    ];
}

/**
 * Returns a shared mysqli connection for tests or null when unavailable.
 */
function getSharedTestConnection(): ?mysqli {
    global $global_connection;

    if ($global_connection instanceof mysqli) {
        return $global_connection;
    }

    $config = getTestDbConfig();
    if ($config === null) {
        return null;
    }

    try {
        $conn = @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name']);
    } catch (mysqli_sql_exception $e) {
        return null;
    }

    if (!$conn) {
        return null;
    }

    $global_connection = $conn;
    return $conn;
}

function setMySqlConnection(MySQL $db, mysqli $conn): bool {
    try {
        $prop = new ReflectionProperty(MySQL::class, 'conn');
        $prop->setAccessible(true);
        $prop->setValue($db, $conn);
        return true;
    } catch (ReflectionException $e) {
        return false;
    }
}

/**
 * Test-safe DB wrapper used by USER/THEUSER classes.
 */
class ParlDB extends MySQL {
    public function __construct() {
        $conn = getSharedTestConnection();
        if (!$conn) {
            return;
        }

        if (!setMySqlConnection($this, $conn)) {
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
    $conn = getSharedTestConnection();
    if (!$conn) {
        return null;
    }

    // Create a MySQL instance that uses this connection
    $db = new MySQL();

    if (!setMySqlConnection($db, $conn)) {
        return null;
    }

    return $db;
}

/**
 * Static helper for integration tests to get a connection.
 */
class TestDatabase {
    public static function tryConnect(): ?MySQL {
        return getTestDatabase();
    }
}




