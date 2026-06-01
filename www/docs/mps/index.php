<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/people.php";

$GLOBALS['this_page'] = 'mps';
if (get_http_var('c4')) {
    $GLOBALS['this_page'] = 'c4_mps';
} elseif (get_http_var('c4x')) {
    $GLOBALS['this_page'] = 'c4x_mps';
}

if (get_http_var('f') != 'csv') {
    $PAGE->page_start();
    $PAGE->stripe_start();
    $format = 'html';
} else {
    $format = 'csv';
}

$args = [];

if (get_http_var('o') == 'f') {
    $args['order'] = 'first_name';
} elseif (get_http_var('o') == 'l') {
    $args['order'] = 'last_name';
} elseif (get_http_var('o') == 'c') {
    $args['order'] = 'constituency';
} elseif (get_http_var('o') == 'p') {
    $args['order'] = 'party';
} elseif (get_http_var('o') == 'e') {
    $args['order'] = 'expenses';
} elseif (get_http_var('o') == 'd') {
    $args['order'] = 'debates';
} elseif (get_http_var('o') == 's') {
    $args['order'] = 'safety';
}

$PEOPLE = new PEOPLE();
$PEOPLE->display('mps', $args, $format);

if (get_http_var('f') != 'csv') {
    $PAGE->stripe_end([
        ['type' => 'include', 'content' => 'mps'],
        ['type' => 'include', 'content' => 'donate']
    ]);
    $PAGE->page_end();
}
