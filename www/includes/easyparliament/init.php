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
