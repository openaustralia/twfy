<?php

/**
 * @file
 * Asks before removing, so a link followed by a machine changes nothing.
 * $actionUrl is the page's own address with the token, $signUpUrl the sign-up page.
 */
?>
<p>Do you want to stop all email alerts to this address?</p>

<form action="<?php echo $this->e($actionUrl) ?>" method="post">
    <p><input type="submit" value="Unsubscribe from all alerts"></p>
</form>

<p>Changed your mind? You can <a href="<?php echo $this->e($signUpUrl) ?>">sign up for an alert again</a> at any time.</p>
