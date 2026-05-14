<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

$this_page = "admin_trackbacks";

$PAGE->page_start();

$PAGE->stripe_start();

$TRACKBACK = new TRACKBACK();
$TRACKBACK->display('recent', ['num' => 30]);


$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'        => 'html',
        'content'    => $menu
    ]
]);

$PAGE->page_end();
