<?php

/**
 * @file
 * Wording that differs from Laravel's.
 *
 * Asking for a link gets the same answer whether or not the address has an account, and so does a reset form
 * with a bad link, so neither tells anyone which email addresses do.
 */

$sent = "If that email address has an account on the website, we've sent it a link to choose a new password. If you only subscribed to email alerts, you won't have an account yet.";
$expired = 'That link has expired or has already been used. You can ask for a new one.';

return [

    'reset' => 'Your password has been changed. Please log in with it.',
    'sent' => $sent,
    'token' => $expired,
    'user' => $expired,

];
