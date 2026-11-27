<?php

/**
 * @file
 * The user comes to this page after clicking an 'add a glossary item' link when they aren't logged in.
 */

// This page will expect a 'ret' value with the URL of the page
// the user should return to after logging in.


$this_page = "userprompt";

include_once "../../../includes/easyparliament/init.php";


$returl = get_http_var('ret');

$message = "Sorry, you must be logged in to add a glossary item.";
$message2 = "You'll be able to add your glossary item straight after.";

$URL = new URL('glossary_addterm');
$URL->insert(['g' => get_http_var('g')]);
$glossary_returl = $URL->generate();
if ($returl == '') {
    $returl = $glossary_returl;
}

$URL = new URL('userjoin');
$URL->insert(['ret' => $returl]);
$joinurl = $URL->generate();


// GET THAT PAGE STARTED!

$PAGE->page_start();

$PAGE->stripe_start();

?>

<p><strong><?php echo $message; ?></strong></p>

<p>If you're not yet a member, then <a href="<?php echo $joinurl; ?>"><strong>join now</strong></a>.</p>

<p>Otherwise, please log in... <?php echo $message2; ?></p>

<?php

$PAGE->login_form();


$PAGE->stripe_end();

$PAGE->page_end();
