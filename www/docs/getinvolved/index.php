<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$this_page = "getinvolved";

$PAGE->page_start();

$PAGE->stripe_start();

include __DIR__ . '/../../includes/easyparliament/staticpages/getinvolved.php';

$PAGE->stripe_end();

$PAGE->page_end();
