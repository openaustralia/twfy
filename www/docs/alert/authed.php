<?php

/**
 * @file
 * Authed.php:
 */

// Returns whether an email address has signed up to a TWFY alert for an MP.
// Uses shared secret for authentication.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: https://www.mysociety.org
//
// $Id: authed.php,v 1.1 2006/05/26 08:44:46 matthew Exp $.

include_once __DIR__ . '/../../includes/easyparliament/init.php';
include_once __DIR__ . '/../../../../phplib/auth.php';

header("Content-Type: text/plain");

$email = get_http_var('email');
$sign = get_http_var('sign');
$pid = get_http_var('pid');
if (!$pid || !ctype_digit($pid)) {
    print 'NOT valid';
} else {
    $authed = auth_verify_with_shared_secret($email, OPTION_AUTH_SHARED_SECRET, $sign);
    if ($authed) {
        
        $criteria = "speaker:$pid";
        $q = parlDBQuery('SELECT alert_id from alerts WHERE email=? AND criteria=? AND confirmed AND NOT deleted', $email, $criteria);
        $already_signed = $q->rows();
        if ($already_signed) {
            print "already signed";
        } else {
            print "NOT signed";
        }
    } else {
        print "NOT authed";
    }
}
