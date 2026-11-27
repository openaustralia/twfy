<?php

/**
 * @file
 * The front controller for the requests Apache hands to Laravel (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 *
 * It loads the legacy bootstrap first, so Laravel's pages can use the legacy page layout.
 */

use Illuminate\Http\Request;

// A page's own index.php can include this file, so say this is the script that is running. Otherwise the folder the
// request arrived at is taken as the base of the URL, and the route is seen as just "/".
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/laravel.php';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

include_once __DIR__ . '/../includes/easyparliament/init.php';

twfy_laravel()->handleRequest(Request::capture());
