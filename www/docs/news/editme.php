<?php 

/* OpenAustralia.org site news */

$all_news = array(

1 => array('Public Launch of OpenAustralia Beta', <<<EOT
We're open to the public! After more than six months work by a small
group of volunteers working in their spare time, we're launching
OpenAustralia to the public. We hope that this site will become an
important part of ensuring a continued and improved fair, democratic
and open Australian Parliamentary system.

This site is about giving you, the Australian public, the tools to easily
follow what goes on in Parliament and be just a little better informed.

You'll hopefully discover with OpenAustralia that what goes on in Parliament
is much more interesting than you might imagine - entertaining even!

What you see on TV or in newspapers is only a small part of what goes on.
OpenAustralia is about giving you access to all the other stuff in an easy
to use and friendly form.

This site is still in beta, which means that we're not totally confident that
we've ironed out all the small problems, but we didn't think it fair to keep
it away from you because of how useful it is. If you do find something
that looks wrong please email matthew at openaustralia dot org.

Enjoy!
EOT
, '2008-6-16 9:00:00', 'Matthew'),

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


