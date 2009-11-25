<?php
// This sidebar is on the very front page of the site.

$this->block_start(array('id'=>'help', 'title'=>"What's all this about?"));

$URL = new URL('about');
$abouturl = $URL->generate();

$URL = new URL('help');
$helpurl = $URL->generate();
?>

<p><a href="http://blog.openaustralia.org/join-us/" onClick="javascript: pageTracker._trackPageview('/outgoing/blog.openaustralia.org/join-us');"><img src="<?=IMAGEPATH."donate_greenL.png"?>" width="108" height="43" border="0" align="right" hspace="4" vspace="5" alt="Donate"></a>
<a href="<?php echo $abouturl; ?>" title="link to About Us page">OpenAustralia.org</a>
is a non-partisan website run by a group of volunteers which aims to
make it easy for people to keep tabs on their representatives in Parliament.</p>

<?php
$this->block_end();
?>
