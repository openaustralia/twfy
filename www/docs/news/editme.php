<?php 

/* TheyWorkForYou.com site news */

$all_news = array(

1 => array('Birth of an Australian TheyWorkForYou', <<<EOT
By hacking, levering, doing terrible things while nobody is looking,
pummeling and other things in that general direction, we've managed
to somehow get a very crude version of an Australian version of
TheyWorkForYou up and running. All credit goes to the UK guys for
putting so much love and labour into a codebase that made this all
really surprisingly straightforward. Thank you!
EOT
, '2007-11-22 17:46:00', 'Matthew'),


);

// General news functions
function news_format_body($content) {
	return "<p>" . str_replace("\n\n", "<p>", $content);
}
function news_format_ref($title) {
	$x = preg_replace("/[^a-z0-9 ]/", "", strtolower($title));
	$x = substr(str_replace(" ", "_", $x), 0, 16);
	return $x;
}
function news_individual_link($date, $title) {
	return WEBPATH . "news/archives/" . str_replace("-", "/", substr($date, 0, 10)) . "/" . news_format_ref($title);
}


