<?php

/**
 * @file
 * Sessions live in the sessions table, so they can be listed and revoked.
 */

return [

    'driver' => 'database',

    // As before, a sign-in lasts until the browser closes, unless the person asked to be remembered.
    'lifetime' => 1440,

    'expire_on_close' => true,

    'table' => 'sessions',

    'cookie' => 'oa_session',

    'secure' => !(defined('DEVSITE') && DEVSITE),

    'http_only' => true,

    'same_site' => 'lax',

];
