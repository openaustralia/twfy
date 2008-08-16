<?php 

/* OpenAustralia.org site news */

$all_news = array(

4 => array('The Senate is Here!', <<<EOT
Just in time for the next sitting, for your civic pleasure, we bring you the Senate. Read the Senate Hansard as
far back as 2006, and get to know those lovely people working on your behalf, the Senators.

No bills pass without the say so of the Senate, so get over there and see what they're up to. Better still,
write to them and ask them what they've been doing on your behalf over the recess.

While we've tried to bring you a perfectly working website, it is in beta, so if you find anything broken,
or see anything wrong, please <a href="mailto:contact@openaustralia.org">drop us a line</a>.
EOT
, '2008-08-18 09:00:00', 'Katherine'),

3 => array('OpenAustralia behind the scenes', <<<EOT
This news feed will tell you about updates to the site.

There have been some pretty exciting developments behind the scenes, which you can follow on our blog at
<a href="http://blog.openaustralia.org">blog.openaustralia.org</a>. There's a link at the bottom of every page as well.

For live updates follow @openaustralia on <a href="http://www.twitter.com/openaustralia">twitter</a>. 
EOT
, '2008-07-06 19:56:00', 'Matthew'),

2 => array('Photos on all representatives page', <<<EOT
On the "<a href="/mps">All Representatives</a>" page you now get a photo for each member so you can more easily
browse through the list and find the person you're looking for.
EOT
, '2008-07-06 19:35:00', 'Matthew'),

1 => array('Public Launch of OpenAustralia Beta', <<<EOT
We're open to the public! After more than six months work by a small
group of volunteers working in their spare time, we're launching
OpenAustralia to the public.

With OpenAustralia we hope to strengthen our traditions of fair and open
democracy in Australia. This site is about giving you, the Australian public, the tools to easily
follow what goes on in Parliament, and be just a little better informed.

You'll hopefully discover with OpenAustralia that what goes on in Parliament
is much more interesting than you might imagine - entertaining even!

What you see on TV or in newspapers is only a small part of what goes on.
OpenAustralia is about giving you access to all the other stuff in an easy
to use and friendly form.

This site is still in beta, which means that we're not totally confident that
we've ironed out all the small problems, but we didn't think it fair to keep
it from you any longer. If you do find something
that looks wrong please email us at contact@openaustralia.org.

Enjoy!
EOT
, '2008-06-16 09:00:00', 'Matthew'),
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


