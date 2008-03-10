<?php
// This sidebar is on the very front page of the site.

$this->block_start(array('id'=>'help', 'title'=>"Did you know this site is run by a charity?"));
?>

<p><a href="https://secure.mysociety.org/donate/"><img align="right" src="<?=WEBPATH."/images/donate_red_flatL.gif"?>" width="100" height="35" border="0" hspace="4" vspace="5" alt="Donate"></a>
Your donation would enable
us to continue to add new features.</p>

<?php
$this->block_end();
?>
