<?php 

/* OpenAustralia.org site news */

$all_news = array(

7 => array('Read the Register of Senators\' Interests here', <<<EOT
Today is a big milestone. We are the first website to make the Register of Senators' Interests available online. This important public document until now has only been available to the small number of people who were able to visit the office in Canberra where the documents are held. In the Register each Senator declares information of financial interests, stocks and shares held, gifts received over a certain value, and memberships of Clubs and Associations.

The register is available on each Senator's page. For example, <a href="/senator/judith_adams/wa#register">have a look at the Register for Senator Judith Adams</a>.

As soon as we can we will also make the Register of Members' Interests available online, the equivalent document for the Representatives. Our main obstacle right now is getting our paper copy of the register scanned. If you have access to a bulk scanner and can help please <a href="mailto:contact&#64;openaustralia.org">contact us</a>.</p>
EOT
, '2009-01-05 11:05:00', 'Matthew'),

6 => array('Government website changes everything', <<<EOT
We've completely rewritten the engine that drives OpenAustralia. We didn't want to, the government (website) made us do it. No really. For a bit of background read our blog post <a href="http://blog.openaustralia.org/2008/10/13/why-is-openaustralia-not-getting-updated/">"Why is OpenAustralia not getting updated?"</a>.

The outage of new update has only been over the last couple of weeks (from 13 Oct) and this all fixed now. I did quit a paying job to make it happen, so if that makes you feel like <a href="http://blog.openaustralia.org/how-can-i-help/">donating some money to us</a>, please go ahead!

Catch up on the debates that happened while we were down. As of next week when parliament resumes, email updates will be back in action too.
EOT
, '2008-11-03 23:57:00', 'Matthew'),

5 => array('A new look OpenAustralia', <<<EOT
OpenAustralia has a lovely and sleek new look courtesy of <a href="http://www.purecaffeine.com/">Nathanael Boehm</a>.

We're always interested in feedback, so let us know what you think by <a href="mailto:contact@openaustralia.org">emailing us</a> at the usual place. Enjoy! 
EOT
, '2008-10-04 12:26:00', 'Matthew'),

4 => array('The Senate is Here!', <<<EOT
Just in time for the next sitting, for your civic pleasure, we bring you the Senate. Read the Senate Hansard as
far back as 2006, and get to know those lovely people working on your behalf, the Senators.

No bills pass without the say so of the Senate, so get over there and see what they're up to. Better still,
write to them and ask them what they've been doing on your behalf over the recess.

While we've tried to bring you a perfectly working website, it is in beta, so if you find anything broken,
or see anything wrong, please <a href="mailto:contact@openaustralia.org">drop us a line</a>.
EOT
, '2008-08-17 19:40:00', 'Katherine'),

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


