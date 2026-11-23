<?php

/**
 * @file
 * The front controller for the requests Apache hands to Laravel (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 *
 * It loads the legacy bootstrap first, so Laravel's pages can use the legacy page layout.
 */

use Illuminate\Http\Request;

include_once __DIR__ . '/../includes/easyparliament/init.php';

twfy_laravel()->handleRequest(Request::capture());
