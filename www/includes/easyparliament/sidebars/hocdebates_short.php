<?php
// This sidebar is on the pages that show all the text of a particular debate.

$URL = new URL('debates');
$debatesurl = $URL->generate();

$this->block_start(array(
	'id'=>'help', 
	'title'=>"What are House debates?", 
	'url'=> $debatesurl . '#help',
	'body'=>false
));
$this->block_end();
?>
