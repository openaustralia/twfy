<?php

/**
 * @file
 */

/**
 * Stub functions that mysql.php depends on at include-time and at runtime.
 * These are replaced by real implementations in the live application.
 */
if (!function_exists('twfy_debug')) {
    function twfy_debug(string $type, string $msg): void {
    }
}

/**
 *
 */
if (!function_exists('getmicrotime')) {
    function getmicrotime(): float {
        return microtime(true);
    }
}

/**
 *
 */
if (!function_exists('get_cookie_var')) {
    function get_cookie_var(string $varname): string {
        return $_COOKIE[$varname] ?? '';
    }
}

if (!defined('CONSTITUENCY_COOKIE')) {
    define('CONSTITUENCY_COOKIE', 'constituency');
}

if (!defined('COOKIEDOMAIN')) {
    define('COOKIEDOMAIN', '');
}

// Define file paths for include_once statements.
if (!defined('BASEDIR')) {
    define('BASEDIR', __DIR__ . '/../www');
}

if (!defined('INCLUDESPATH')) {
    define('INCLUDESPATH', __DIR__ . '/../www/includes/');
}

if (!defined('EASYPARLIAMENTPATH')) {
    define('EASYPARLIAMENTPATH', __DIR__ . '/../www/includes/easyparliament/');
}

if (!defined('METADATAPATH')) {
    define('METADATAPATH', __DIR__ . '/../www/includes/easyparliament/metadata.php');
}

if (!defined('FILEIMAGEPATH')) {
    define('FILEIMAGEPATH', __DIR__ . '/../www/docs/images/');
}

if (!defined('IMAGEPATH')) {
    define('IMAGEPATH', '/images/');
}

if (!defined('WEBPATH')) {
    define('WEBPATH', '/');
}

if (!defined('LONGDATEFORMAT')) {
    define('LONGDATEFORMAT', 'j F Y');
}

if (!function_exists('format_date')) {
    function format_date($date, $format) {
        $timestamp = strtotime((string) $date);
        if ($timestamp === false) {
            return '';
        }
        return date((string) $format, $timestamp);
    }
}

require_once __DIR__ . '/../www/includes/mysql.php';

// eloquent.php expects DB_* to be defined as constants (as conf/general does
// in production). Tests pass them in as env vars via phpunit.xml; promote them
// to constants here. Use empty strings when absent so unit-only `make test`
// runs (which never touch the DB) can still boot Capsule.
foreach (['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'] as $_dbConst) {
    if (!defined($_dbConst)) {
        $_dbVal = getenv($_dbConst);
        define($_dbConst, $_dbVal === false ? '' : $_dbVal);
    }
}
unset($_dbConst, $_dbVal);

require_once INCLUDESPATH . 'eloquent.php';

/**
 * Returns the shared ParlDB instance, with test override support.
 */
if (!function_exists('getParlDB')) {
    function getParlDB() {
        global $parldb_override;
        if ($parldb_override !== null) {
            return $parldb_override;
        }

        static $db = null;

        if ($db === null) {
            $db = new ParlDB();
        }

        return $db;
    }
}

/**
 * Convenience wrapper for getParlDB()->query().
 */
if (!function_exists('parlDBQuery')) {
    function parlDBQuery($sql, ...$params) {
        return getParlDB()->query($sql, ...$params);
    }
}

require_once EASYPARLIAMENTPATH . 'member.php';
require_once EASYPARLIAMENTPATH . 'alert.php';
require_once BASEDIR . '/docs/api/api_getConstituencies.php';

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

/**
 *
 */
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

require_once EASYPARLIAMENTPATH . 'user.php';

/**
 * Wrapper that creates a database connection for tests without calling exit().
 * Returns a MySQL instance if successful, null otherwise.
 */
function getTestDatabase(): ?MySQL {
    $conn = getSharedTestConnection();
    if (!$conn) {
        return null;
    }

    // Create a MySQL instance that uses this connection.
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

    /**
     *
     */
    public static function tryConnect(): ?MySQL {
        return getTestDatabase();
    }

}

/**
 * Base class for integration tests that need a database.
 *
 * Each test runs inside a transaction that is rolled back in tearDown, so no
 * test data ever persists to the database.  The connection must be the same
 * mysqli handle used throughout the request (getSharedTestConnection()).
 */
abstract class TransactionalTestCase extends \PHPUnit\Framework\TestCase {

    /**
     * Override in a subclass to disable mysqli transaction wrapping.
     */
    protected function useMysqliTransaction(): bool {
        return true;
    }

    /**
     * Override in a subclass to disable Eloquent transaction wrapping.
     */
    protected function useEloquentTransaction(): bool {
        return true;
    }

    protected function setUp(): void {
        parent::setUp();
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->fail('Database connection not available (check DB_HOST/DB_USER/DB_PASSWORD/DB_NAME)');
        }
        if ($this->useMysqliTransaction()) {
            mysqli_begin_transaction($conn);
        }

        // MEMBER and other code paths now use Eloquent for some queries,
        // so start a transaction there too to keep tests isolated.
        if ($this->useEloquentTransaction()) {
            \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();
        }
    }

    protected function tearDown(): void {
        if ($this->useEloquentTransaction()) {
            $eloquentConnection = \Illuminate\Database\Capsule\Manager::connection();
            if ($eloquentConnection->transactionLevel() > 0) {
                $eloquentConnection->rollBack();
            }
        }

        $conn = getSharedTestConnection();
        if ($conn && $this->useMysqliTransaction()) {
            mysqli_rollback($conn);
        }
        parent::tearDown();
    }

}
