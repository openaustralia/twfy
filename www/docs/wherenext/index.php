<?php

include_once "../../includes/easyparliament/init.php";

$this_page = "wherenext";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/wherenext.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>
