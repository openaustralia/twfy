<?php

/**
 * @file
 * Wording that differs from Laravel's.
 *
 * Asking for a link gets the same answer whether or not the address has an account, so the form doesn't tell anyone
 * which email addresses do.
 */

$answer = "If that email address has an account on the website, we've sent it a link to choose a new password. If you only subscribed to email alerts, you won't have an account yet.";

return [

    'reset' => 'Your password has been changed. Please log in with it.',
    'sent' => $answer,
    'user' => $answer,
    'throttled' => $answer,
    'token' => 'That link has expired or has already been used. You can ask for a new one.',

];
