<?php

/**
 * @file
 */

$this_page = "about";

include_once __DIR__ . '/../../includes/easyparliament/init.php';

$PAGE->page_start();

$PAGE->stripe_start();

include __DIR__ . '/../../includes/easyparliament/staticpages/about.php';

$PAGE->stripe_end();

$PAGE->page_end();
