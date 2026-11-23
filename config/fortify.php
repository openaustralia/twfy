<?php

/**
 * @file
 * Login and password reset, at the URLs the legacy pages already link to.
 */

use Laravel\Fortify\Features;

return [

    'paths' => [
        'login' => 'user/login',
        'password' => [
            'request' => 'user/password',
            'email' => 'user/password',
            'reset' => 'user/password/reset/{token}',
            'update' => 'user/password/reset',
        ],
    ],

    'home' => '/',

    'features' => [Features::resetPasswords()],

];
