<?php

/**
 * @file
 * This sidebar is on the search page.
 */

$this->block_start([
    'id' => 'help',
    'title' => "Search Tips"
]);

include INCLUDESPATH . 'easyparliament/staticpages/search_help.php';

$this->block_end();
