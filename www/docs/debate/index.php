<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";

$this_page = "debate";

// For displaying a SINGLE speech from a debate, with comments and 
// an 'Add comment' form.


if (get_http_var('id') != '') {
	// We have the id of the gid of a Hansard item to display, so show it.

	$args = array (
		'gid' => get_http_var('id'),
		'glossarise' => 1,
		'sort' => 'regexp_replace',
	);
	
	$DEBATELIST = new DEBATELIST;
	$GLOSSARY = new GLOSSARY($args);
	
	$result = $DEBATELIST->display('gid', $args);
	// If it is a redirect, change URL
	if (is_string($result)) {
		$URL = new URL('debate');
		$URL->insert( array('id'=>$result) );
		header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
		exit;
	}

	
	// 12 is speech
	// 13 is procedural - see http://parl.stand.org.uk/cgi-bin/moin.cgi/DataSchema	
	if ($DEBATELIST->htype() == '12' ||
		$DEBATELIST->htype() == '13'
		) {
		
		$PAGE->stripe_start('side', 'comments');
	
		// Display all comments for this ep object.
		$COMMENTLIST = new COMMENTLIST;
		
		$args['user_id'] = get_http_var('u');	// For highlighting their comments.
		$args['epobject_id'] = $DEBATELIST->epobject_id();
		
		$COMMENTLIST->display('ep', $args);
		
		$PAGE->stripe_end();

	
#		$TRACKBACK = new TRACKBACK;	
#		$TRACKBACK->display('epobject_id', $commendata);
	}
	

	
} else {
	$PAGE->error_message("We need a gid");
}

$PAGE->page_end();



?>
