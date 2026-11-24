<?php

/**
 * @file
 * Where Blade finds its views and keeps them compiled.
 */

return [

    // The Plates templates live here too. Blade views end in .blade.php, which Plates never loads.
    'paths' => [dirname(__DIR__) . '/www/resources/views'],

    // Apache can't write into a release, so on the server Capistrano links this to a folder in shared/.
    'compiled' => dirname(__DIR__) . '/storage/framework/views',

];
