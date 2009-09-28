<?php
$_SERVER['DEVICE_TYPE'] = "mobile";

include_once "../../includes/easyparliament/init.php";

$this_page = "help";

$PAGE->page_start_mobile();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/help.php';

//$PAGE->stripe_end();

//$PAGE->page_end();
$PAGE->page_end_mobile();

?>
