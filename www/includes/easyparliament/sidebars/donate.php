<?php
// This sidebar is on the very front page of the site.

$this->block_start(array('id'=>'help', 'title'=>"Did you know this site is run by a charity and volunteers?"));
?>

<p style="padding-bottom: 30px"><a href="https://www.openaustraliafoundation.org.au/donate/" onClick="javascript: pageTracker._trackPageview('/outgoing/www.openaustraliafoundation.org.au/donate');"><img align="right" src="<?=IMAGEPATH."donate_greenL.png"?>" width="108" height="43" border="0" hspace="4" vspace="5" alt="Donate"></a>
Your donation would enable
us to continue to add new features.</p>

<?php
$this->block_end();
?>
