<?php 

/* TheyWorkForYou.com site news */

$all_news = array(

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


