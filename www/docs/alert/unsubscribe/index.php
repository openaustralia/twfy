<?php

/**
 * @file
 * Name: /alert/unsubscribe/index.php.
 *
 * One-click unsubscribe (RFC 8058) for alert emails. The List-Unsubscribe
 * header of every alert email points here, with t= the registration token of
 * one of the address's alerts.
 *
 * A POST, which is what a mail app's Unsubscribe button sends, removes every
 * alert at that address. A GET removes nothing and shows a page with a button
 * to do the same, because mail scanners and browsers prefetch links and a
 * link followed by a machine must not unsubscribe anyone.
 *
 * The link at the foot of each alert section, /D/, is separate and still
 * removes one alert.
 */

include_once __DIR__ . "/../../../includes/easyparliament/init.php";

$ALERT = new ALERT();
$token = get_http_var('t');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $removed = $ALERT->delete_all_at_address($token);
    if ($removed === false) {
        http_response_code(400);
        unsubscribe_error();
    } else {
        unsubscribe_success();
    }
} elseif ($ALERT->email_for_token($token) === null) {
    http_response_code(400);
    unsubscribe_error();
} else {
    unsubscribe_confirm($token);
}

/**
 * Asks before removing, so a link followed by a machine changes nothing.
 */
function unsubscribe_confirm($token) {
    global $PAGE, $this_page;

    $this_page = 'alertunsubscribeconfirm';
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>

    <p>Do you want to stop all email alerts to this address?</p>

    <form action="<?php echo WEBPATH ?>alert/unsubscribe/?t=<?php echo htmlspecialchars(urlencode($token)) ?>" method="post">
        <p><input type="submit" value="Unsubscribe from all alerts"></p>
    </form>

    <p>Changed your mind? You can <a href="<?php echo WEBPATH ?>alert/">sign up for an alert again</a> at any time.</p>

    <?php
    $PAGE->stripe_end();
    $PAGE->page_end();
}

/**
 * Confirms what happened, and links back to sign up again.
 */
function unsubscribe_success() {
    global $PAGE, $this_page;

    $this_page = 'alertunsubscribesucceeded';
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>

    <p>Your alerts have been unsubscribed. You will no longer receive alert emails at this address.</p>

    <p><strong>If you didn't mean to do this, you can <a href="<?php echo WEBPATH ?>alert/">sign up for an alert again</a>.</strong></p>

    <?php
    $PAGE->stripe_end();
    $PAGE->page_end();
}

/**
 * A friendly error, not a normal one.
 */
function unsubscribe_error() {
    global $PAGE, $this_page;

    $this_page = 'alertunsubscribefailed';
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>

    <p>The link you followed to reach this page appears to be incomplete.</p>

    <p>If you clicked a link in your alert email you may need to copy and paste the entire link into the address bar of your web browser and try again.</p>

    <p>If you still get this message, please <a href="mailto:<?php echo CONTACTEMAIL; ?>">email us</a> and we'll help out.</p>

    <?php
    $PAGE->stripe_end();
    $PAGE->page_end();
}
