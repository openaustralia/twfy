<?php

/**
 * @file
 * Where Blade finds its views and keeps them compiled.
 */

$compiled = dirname(__DIR__) . '/storage/framework/views';

return [

    // The Plates templates live here too. Blade views end in .blade.php, which Plates never loads.
    'paths' => [dirname(__DIR__) . '/www/resources/views'],

    // Apache can't always write into a release directory, so fall back to the system's temp folder.
    'compiled' => is_writable($compiled) ? $compiled : sys_get_temp_dir() . '/twfy-blade',

];
