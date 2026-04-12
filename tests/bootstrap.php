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
 * Test database class that can be instantiated with credentials.
 */
class TestDB extends MySQL
{
    public function __construct(string $host, string $user, string $pass, string $name) {
        parent::__construct();
        $this->init($host, $user, $pass, $name);
    }
}

/**
 * Global test database connection, available if DB_HOST etc. are set.
 * Used by integration tests; unit tests don't need this.
 */
global $TEST_DB;
$TEST_DB = null;

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASSWORD');
$db_name = getenv('DB_NAME');

if ($db_host && $db_user && $db_name) {
    $TEST_DB = new TestDB($db_host, $db_user, $db_pass ?: '', $db_name);
}

