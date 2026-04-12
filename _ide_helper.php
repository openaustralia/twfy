<?php

/**
 * @file
 * IDE helper stub — defines constants from conf/general (which has no .php extension
 * and is therefore not indexed by the PHP language server).
 *
 * This file is NOT included at runtime. It exists purely so VS Code / Intelephense
 * can resolve constant references across the codebase.
 */

// MySQL database.
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'twfyuser');
define('DB_PASSWORD', 'twfypass');
define('DB_NAME', 'twfy');

// Domains.
define('DOMAIN', 'example.com');
define('COOKIEDOMAIN', 'example.com');

// Email addresses.
define('CONTACTEMAIL', 'contact@example.com');
define('REPORTLIST', CONTACTEMAIL);
define('BCCADDRESS', CONTACTEMAIL);
define('BUGSLIST', 'bugs@example.com');
define('ALERT_STATS_EMAILS', BUGSLIST);
define('ALERT_STATS_SENDER', CONTACTEMAIL);

// Filesystem / web paths.
define('BASEDIR', __DIR__ . "/www/docs/");
define('WEBPATH', '/');
define('INCLUDESPATH', BASEDIR . "/../includes/");
define('IMAGEPATH', WEBPATH . "images/");
define('FILEIMAGEPATH', BASEDIR . "images/");
define('REGMEMPDFPATH', "regmem/scan/");
define('METADATAPATH', BASEDIR . "/../includes/easyparliament/metadata.php");
define('RAWDATA', __DIR__ . '/../openaustralia/pwdata/');
define('PWMEMBERS', __DIR__ . '/../openaustralia/pwdata/members/');
define('DBBACKUP', __DIR__ . '/tmp/dbbackup/');

// Search
// Non-empty in production/staging and thus uses XAPIAN instead of mysql search.
define('XAPIANDB', '');

// Recess.
define('RECESSFILE', RAWDATA . "/parl-recesses.txt");

// Dev / debug
// set $DEVSITE in ENV if you want a lot of debugging output.
define('DEVSITE', getenv('DEVSITE') !== FALSE);
// Add this and a number to the URL (eg '?debug=1') to view debug info.
define('DEBUGTAG', 'debug');

// Timezone.
define('TIMEZONE', 'Australia/Sydney');

// Postcode lookup.
define('POSTCODE_SEARCH_DOMAIN', '');
define('POSTCODE_SEARCH_PORT', '80');
define('POSTCODE_SEARCH_PATH', "somescript.php?postcode=");

// Tracking. (off)
define('OPTION_TRACKING', 0);
define('OPTION_TRACKING_URL', '');
define('OPTION_TRACKING_SECRET', '');

// Auth / external services.
define('OPTION_AUTH_SHARED_SECRET', '');
define('OPTION_HEARFROMYOURMP_BASE_URL', '');
define('OPTION_MAPIT_URL', '');
define('OPTION_GAZE_URL', '');
define('OPTION_PHP_DEBUG_LEVEL', 0);

// Misc.
define('ADDTHIS_USERNAME', '');
define('PUBLICWHIP_HOST', '');
define('DISPLAY_VOTING_DATA', TRUE);

// Defined in init.php.
define('ALLOWCOMMENTS', TRUE);
define('ALLOWTRACKBACKS', TRUE);
define('LONGERDATEFORMAT', 'l, j F Y');
define('LONGDATEFORMAT', 'j F Y');
define('SHORTDATEFORMAT', 'j M Y');
define('TIMEFORMAT', 'g:i a');
define('SHORTDATEFORMAT_SQL', '%e %b %Y');
define('TIMEFORMAT_SQL', '%l:%i %p');
define('POSTCODE_COOKIE', 'eppc');
define('CONSTITUENCY_COOKIE', 'constituency');
define('STARTTIME', 0.0);
define('STARTTIMES', 0);
define('STARTTIMEU', 0);
