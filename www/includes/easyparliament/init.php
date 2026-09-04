<?php

/**
 * @file
 * First some things to help make our PHP nicer and betterer.
 */

use Sentry\SentrySdk;
use Sentry\Tracing\TransactionContext;

error_reporting(E_ALL);
ini_set('memory_limit', 32 * 1024 * 1024);

/********************************************************************************
 * Now some constants that are the same for live and dev versions
 * (unlike those variables in conf/general)
 ********************************************************************************/


// In case we need to switch these off globally at some point...
define("ALLOWCOMMENTS", true);

// These variables are so we can keep date/time formats consistent across the site
// and change them easily.
// Formats here: https://www.php.net/manual/en/function.date.php
// Monday, 31 December 2003.
define("LONGERDATEFORMAT", "l, j F Y");
// 31 December 2003
define("LONGDATEFORMAT", "j F Y");
// 31 Dec 2003
define("SHORTDATEFORMAT", "j M Y");
// 11:59 pm
define("TIMEFORMAT", "g:i a");

// 31 Dec 2003
define("SHORTDATEFORMAT_SQL", "%e %b %Y");
// 11:59 PM
define("TIMEFORMAT_SQL", "%l:%i %p");

// Where we store the postcode of users if they search for an MP by postcode.
define('POSTCODE_COOKIE', 'eppc');
define('CONSTITUENCY_COOKIE', 'constituency');

/********************************************************************************
 * And now all the files we'll include on every page.
 ********************************************************************************/

require_once __DIR__ . '/../../../vendor/autoload.php';

include_once __DIR__ . '/../../../conf/general';
include_once __DIR__ . '/../utility.php';
twfy_debug_timestamp("after including utility.php");

// Set the default timezone.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(TIMEZONE);
}

// The error_handler function is in includes/utility.php.
$old_error_handler = set_error_handler("error_handler");

// Report uncaught errors/exceptions to Sentry, if configured (conf/general).
// Sentry::init() registers its own error/exception/shutdown handlers, and
// by default chains to whatever was already registered, so error_handler
// above still runs as before - this adds reporting, it doesn't replace it.
if (defined('SENTRY_DSN') && SENTRY_DSN) {
    \Sentry\init([
        'dsn' => SENTRY_DSN,
        'environment' => defined('SENTRY_ENVIRONMENT') ? SENTRY_ENVIRONMENT : 'development',
        // Off (0) by default in this SDK. 10%, not 100%: this is a public,
        // fairly high-traffic site, and a transaction is sent to Sentry on
        // every sampled request - full sampling risks the account's
        // transaction quota. 10% still gives a representative statistical
        // view of DB query volume/latency (mysql.php's MySQL::query()).
        // send_email() (utility.php) starts its own transaction regardless
        // of this rate - email sending is comparatively rare, and we want
        // every send counted, not a sample of them.
        'traces_sample_rate' => 0.1,
    ]);

    // One transaction per request (web page or CLI script - alertmailer.php
    // and other scripts/*.php all include this file too), so DB query spans
    // have a parent to attach to; \Sentry\trace() is a no-op with no active
    // transaction. SCRIPT_NAME (eg "/mp/index.php"), not REQUEST_URI: the
    // latter includes query strings/IDs, which would give every request its
    // own transaction *name* instead of grouping same-page requests together.
    $sentryTransactionContext = new TransactionContext(
        PHP_SAPI === 'cli' ? ($_SERVER['argv'][0] ?? 'cli') : ($_SERVER['SCRIPT_NAME'] ?? 'unknown')
    );
    $sentryTransactionContext->setOp(PHP_SAPI === 'cli' ? 'cli.script' : 'http.server');
    $sentryRequestTransaction = \Sentry\startTransaction($sentryTransactionContext);
    SentrySdk::getCurrentHub()->setSpan($sentryRequestTransaction);
    register_shutdown_function(function () use ($sentryRequestTransaction) {
        $sentryRequestTransaction->finish();
    });
}

// The time the page starts, so we can display the total at the end.
// getmicrotime() is in utiltity.php.
define("STARTTIME", getmicrotime());
if (!isset($_SERVER['WINDIR'])) {
    $rusage = getrusage();
    define('STARTTIMES', $rusage['ru_stime.tv_sec'] * 1000000 + $rusage['ru_stime.tv_usec']);
    define('STARTTIMEU', $rusage['ru_utime.tv_sec'] * 1000000 + $rusage['ru_utime.tv_usec']);
}
include_once __DIR__ . '/../data.php';
include_once __DIR__ . '/../mysql.php';
include_once __DIR__ . '/../eloquent.php';

/**
 *
 */
class ParlDB extends MySQL {

    /**
     *
     */
    public function __construct() {
        $this->init(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }

}

/**
 * Returns the shared ParlDB instance for this request.
 * In tests, set $GLOBALS['parldb_override'] to inject a mock/test DB.
 */
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

/**
 * Convenience wrapper for getParlDB()->query().
 */
function parlDBQuery($sql, ...$params) {
    return getParlDB()->query($sql, ...$params);
}

include_once __DIR__ . '/../url.php';
include_once __DIR__ . '/../lib_filter.php';
include_once __DIR__ . '/../easyparliament/skin.php';
include_once __DIR__ . '/../easyparliament/user.php';
include_once __DIR__ . '/../easyparliament/page.php';
include_once __DIR__ . '/../easyparliament/hansardlist.php';
include_once __DIR__ . '/../easyparliament/commentlist.php';

// Initialise searchlogging.
global $SEARCHLOG;
$SEARCHLOG = new SEARCHLOG();
include_once __DIR__ . '/../easyparliament/comment.php';

// Added in as new module by Richard Allan MP.
include_once __DIR__ . '/../easyparliament/alert.php';

twfy_debug_timestamp("at end of init.php");
