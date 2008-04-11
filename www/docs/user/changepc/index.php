<?php

// For a non-logged-in user changing their postcode.

include_once "../../../includes/easyparliament/init.php";

$this_page = "userchangepc";

if (get_http_var('forget') == 't') {
	// The user clicked the 'Forget' link.
	$THEUSER->unset_constituency_cookie();
	
	// The cookie will have already been read for this page, so we need to reload.
	$URL = new URL($this_page);
	header("Location: http://" . DOMAIN . $URL->generate());
}

if (!$THEUSER->constituency_is_set()) {
	// Change it from 'Change your postcode'.
	$DATA->set_page_metadata($this_page, 'title', 'Enter your postcode');
}


$PAGE->page_start();

$PAGE->stripe_start();


$PAGE->postcode_form();


$PAGE->stripe_end();

$PAGE->page_end();

?>
