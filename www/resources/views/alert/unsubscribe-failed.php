<?php

/**
 * @file
 * A friendly error, not a normal one. $contactEmail is the address to write to.
 */
?>
<p>The link you followed to reach this page appears to be incomplete.</p>

<p>If you clicked a link in your alert email you may need to copy and paste the entire link into the address bar of your web browser and try again.</p>

<p>If you still get this message, please <a href="mailto:<?php echo $this->e($contactEmail) ?>">email us</a> and we'll help out.</p>
