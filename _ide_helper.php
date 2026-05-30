<?php
/**
 * IDE helper stub — defines constants from conf/general (which has no .php extension
 * and is therefore not indexed by the PHP language server).
 *
 * This file is NOT included at runtime. It exists purely so VS Code / Intelephense
 * can resolve constant references across the codebase.
 */

// MySQL database
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

// Domains
define('DOMAIN', '');
define('COOKIEDOMAIN', '');

// Email addresses
define('CONTACTEMAIL', '');
define('REPORTLIST', '');
define('BCCADDRESS', '');
define('BUGSLIST', '');
define('ALERT_STATS_EMAILS', '');
define('ALERT_STATS_SENDER', '');

// Filesystem / web paths
define('BASEDIR', '');
define('WEBPATH', '/');
define('INCLUDESPATH', '');
define('IMAGEPATH', '');
define('FILEIMAGEPATH', '');
define('REGMEMPDFPATH', '');
define('METADATAPATH', '');
define('RAWDATA', '');
define('PWMEMBERS', '');
define('DBBACKUP', '');

// Search
define('XAPIANDB', '');

// Recess
define('RECESSFILE', '');

// Dev / debug
define('DEVSITE', false);
define('DEBUGTAG', 'debug');

// Timezone
define('TIMEZONE', 'Australia/Sydney');

// Postcode lookup
define('POSTCODE_SEARCH_DOMAIN', '');
define('POSTCODE_SEARCH_PORT', '80');
define('POSTCODE_SEARCH_PATH', '');

// Tracking
define('OPTION_TRACKING', 0);
define('OPTION_TRACKING_URL', '');
define('OPTION_TRACKING_SECRET', '');

// Auth / external services
define('OPTION_AUTH_SHARED_SECRET', '');
define('OPTION_HEARFROMYOURMP_BASE_URL', '');
define('OPTION_MAPIT_URL', '');
define('OPTION_GAZE_URL', '');
define('OPTION_PHP_DEBUG_LEVEL', 0);

// Misc
define('ADDTHIS_USERNAME', '');
define('PUBLICWHIP_HOST', '');
define('DISPLAY_VOTING_DATA', true);

// Defined in init.php
define('ALLOWCOMMENTS', true);
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
