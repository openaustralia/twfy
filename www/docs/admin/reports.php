<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

$GLOBALS['this_page'] = "admin_commentreports";


$PAGE->page_start();

$PAGE->stripe_start();


// Get the most recent comment reports.
$LIST = new COMMENTREPORTLIST();
$LIST->display();


$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'        => 'html',
        'content'    => $menu
    ]
]);


$PAGE->page_end();
