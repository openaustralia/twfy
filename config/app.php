<?php

/**
 * @file
 * Only what differs from Laravel's defaults. Settings come from conf/general, like the rest of twfy.
 */

return [

    'name' => 'OpenAustralia',

    'env' => defined('SENTRY_ENVIRONMENT') ? SENTRY_ENVIRONMENT : 'production',

    'debug' => defined('DEVSITE') && DEVSITE,

    'url' => defined('DOMAIN') ? (defined('DEVSITE') && DEVSITE ? 'http://' : 'https://') . DOMAIN : 'http://localhost',

    // Laravel sets PHP's zone from this when it boots, so it must match the legacy pages' TIMEZONE.
    'timezone' => defined('TIMEZONE') ? TIMEZONE : 'UTC',

    'key' => defined('APP_KEY') ? APP_KEY : '',

    // No facade aliases: Laravel's global class names, such as URL, would clash with legacy classes.
    'aliases' => [],

];
