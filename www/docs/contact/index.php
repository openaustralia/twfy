<?php

/**
 * @file
 */

$this_page = "contact";

include_once __DIR__ . '/../../includes/easyparliament/init.php';

$PAGE->page_start();

$PAGE->stripe_start();

include __DIR__ . '/../../includes/easyparliament/staticpages/contact.php';

$PAGE->stripe_end();

$PAGE->page_end();
