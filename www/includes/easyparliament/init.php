<?php

/**
 * @file
 * First some things to help make our PHP nicer and betterer.
 */

use Sentry\State\Scope;
use Sentry\Event;

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
//
// This is the canonical OAF Sentry configuration (see docs/monitoring.md in
// the infrastructure repo; change it there first, then update every copy).
if (defined('SENTRY_DSN') && SENTRY_DSN) {
    // Scrubs email-address-shaped strings from breadcrumbs and request query
    // strings, cookies, headers, and bodies, in line with the Australian
    // Privacy Principles. The pattern matches common email address formats.
    $sentry_scrub_event = function (Event $event) {
        $pattern = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';
        $scrub = function ($value) use (&$scrub, $pattern) {
            if (is_string($value)) {
                return preg_replace($pattern, '[FILTERED]', $value);
            }
            if (is_array($value)) {
                return array_map($scrub, $value);
            }
            return $value;
        };
        $breadcrumbs = [];
        foreach ($event->getBreadcrumbs() as $crumb) {
            if ($crumb->getMessage() !== null) {
                $crumb = $crumb->withMessage($scrub($crumb->getMessage()));
            }
            foreach ($crumb->getMetadata() as $name => $value) {
                $crumb = $crumb->withMetadata($name, $scrub($value));
            }
            $breadcrumbs[] = $crumb;
        }
        $event->setBreadcrumb($breadcrumbs);

        $request = $event->getRequest();
        foreach (['query_string', 'cookies', 'headers', 'data'] as $field) {
            if (array_key_exists($field, $request)) {
                $request[$field] = $scrub($request[$field]);
            }
        }
        $event->setRequest($request);

        return $event;
    };

    // The full git SHA of the deployed umbrella openaustralia repo, from the
    // REVISION file Capistrano writes at the deploy root (one level above
    // this twfy submodule). Absent in local development.
    $sentry_revision_file = dirname(__DIR__, 4) . '/REVISION';
    $sentry_release = is_readable($sentry_revision_file) ? trim(file_get_contents($sentry_revision_file)) : null;

    \Sentry\init([
        'dsn' => SENTRY_DSN,
        // The Sentry environment is always the stage name, templated into
        // conf/general by the infrastructure repo.
        'environment' => defined('SENTRY_ENVIRONMENT') ? SENTRY_ENVIRONMENT : 'development',
        'release' => $sentry_release,
        'traces_sample_rate' => 0.1,
        // Include IP addresses and request headers for debugging context;
        // email-address-shaped strings in selected event fields are scrubbed.
        'send_default_pii' => true,
        'max_request_body_size' => 'never',
        'before_send' => $sentry_scrub_event,
        'before_send_transaction' => $sentry_scrub_event,
    ]);
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

// Attach the signed-in user to Sentry events so errors show who was
// affected. The id only, never email or name - look the id up in the admin
// backend if you need to contact someone about an error (Australian Privacy
// Principles, and the canonical configuration in the infrastructure repo's
// docs/monitoring.md).
if (defined('SENTRY_DSN') && SENTRY_DSN && $THEUSER->isloggedin()) {
    \Sentry\configureScope(function (Scope $scope) use ($THEUSER) {
        $scope->setUser(['id' => (string) $THEUSER->user_id()]);
    });
}

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
