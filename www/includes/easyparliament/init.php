<?php

/**
 * @file
 * First some things to help make our PHP nicer and betterer.
 */

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

include_once __DIR__ . '/../../../conf/general';

// Composer autoload (for OpenTelemetry and other vendored libs). Optional:
// in some minimal CLI contexts vendor/ may not be present, so don't hard-fail.
$__twfy_autoload = __DIR__ . '/../../../vendor/autoload.php';
if (is_readable($__twfy_autoload)) {
    require_once $__twfy_autoload;
}
unset($__twfy_autoload);

// OpenTelemetry bootstrap. No-op if OTEL_EXPORTER_OTLP_ENDPOINT is empty/undefined.
include_once __DIR__ . '/../otel.php';
otel_init();

// For HTTP requests, open a root server span covering the whole request. The
// shutdown handler registered by otel_init() will flush spans at end of process;
// we close the span explicitly here on shutdown so its duration is accurate.
if (PHP_SAPI !== 'cli' && otel_tracer() !== null) {
    $__twfy_root_span = otel_start_root_span(
        sprintf('%s %s', $_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/'),
        [
            'http.request.method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'url.path'            => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/',
            'url.scheme'          => !empty($_SERVER['HTTPS']) ? 'https' : 'http',
            'server.address'      => $_SERVER['HTTP_HOST'] ?? '',
            'user_agent.original' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'client.address'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
        \OpenTelemetry\API\Trace\SpanKind::KIND_SERVER,
    );
    register_shutdown_function(static function () {
        global $__twfy_root_span;
        if (!empty($__twfy_root_span)) {
            $status = http_response_code();
            if (is_int($status) && $__twfy_root_span['span'] !== null) {
                $__twfy_root_span['span']->setAttribute('http.response.status_code', $status);
            }
            otel_end_root_span($__twfy_root_span);
            $__twfy_root_span = null;
        }
    });
}

include_once __DIR__ . '/../utility.php';
twfy_debug_timestamp("after including utility.php");

// Set the default timezone.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(TIMEZONE);
}

// The error_handler function is in includes/utility.php.
$old_error_handler = set_error_handler("error_handler");

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
