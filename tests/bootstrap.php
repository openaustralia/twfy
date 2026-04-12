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
