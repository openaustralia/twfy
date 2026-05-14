<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/init.php';

$this_page = "help";

$PAGE->page_start();

$PAGE->stripe_start();

include __DIR__ . '/../../includes/easyparliament/staticpages/help.php';

$PAGE->stripe_end();

$PAGE->page_end();
