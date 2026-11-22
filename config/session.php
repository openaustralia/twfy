<?php

/**
 * @file
 * Sessions live in the sessions table, so they can be listed and revoked.
 */

return [

    'driver' => 'database',

    'table' => 'sessions',

    'cookie' => 'oa_session',

    'secure' => !(defined('DEVSITE') && DEVSITE),

    'http_only' => true,

    'same_site' => 'lax',

];
