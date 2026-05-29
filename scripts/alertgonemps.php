<?php

/**
 * @file
 * Name: alertgonemps.php
 * Description: Mailer for those whose MP has gone
 * $Id: alertgonemps.php,v 1.1 2006/04/27 14:20:20 twfy-live Exp $
 */

/**
 * Look up a member's left_house date and full name by person_id.
 *
 * @return array{left_house: string, name: string}|null
 */
function get_member_departure_info(int $person_id): ?array {
    $q = parlDBQuery(
        'SELECT first_name, last_name, MAX(left_house) as l FROM member WHERE person_id = ? GROUP BY first_name, last_name',
        $person_id
    );
    if ($q->rows() === 0) {
        return null;
    }
    return [
        'left_house' => $q->field(0, 'l'),
        'name' => $q->field(0, 'first_name') . ' ' . $q->field(0, 'last_name'),
    ];
}

/**
 * Look up a user_id by email address.
 *
 * @return int The user_id, or 0 if not found.
 */
function get_user_id_by_email(string $email): int {
    $q = parlDBQuery('SELECT user_id FROM users WHERE email = ?', $email);
    if ($q->rows() > 0) {
        return (int) $q->field(0, 'user_id');
    }
    return 0;
}

/**
 * Filter alerts to only speaker alerts for members who have left.
 *
 * @param array $alertdata Array of alert rows with 'email' and 'criteria' keys.
 * @return array{alerts: array, mp_count: int, registered: int, unregistered: int}
 *   'alerts' is an array of ['email' => string, 'person_id' => int, 'name' => string, 'user_id' => int]
 */
function find_gone_mp_alerts(array $alertdata): array {
    $member_cache = [];
    $current_email = '';
    $current_user_id = 0;
    $registered = 0;
    $unregistered = 0;
    $mp_ids = [];
    $results = [];

    foreach ($alertdata as $alertitem) {
        $email = $alertitem['email'];
        $criteria = $alertitem['criteria'];
        if (!strstr($criteria, 'speaker:')) {
            continue;
        }

        preg_match('#speaker:(\d+)#', $criteria, $m);
        if (empty($m[1])) {
            continue;
        }
        $person_id = (int) $m[1];

        if (!isset($member_cache[$person_id])) {
            $member_cache[$person_id] = get_member_departure_info($person_id);
        }
        $info = $member_cache[$person_id];
        if ($info === null || $info['left_house'] === '9999-12-31') {
            continue;
        }

        if ($email !== $current_email) {
            $current_email = $email;
            $current_user_id = get_user_id_by_email($email);
            if ($current_user_id > 0) {
                $registered++;
            } else {
                $unregistered++;
            }
        }

        $results[] = [
            'email' => $email,
            'person_id' => $person_id,
            'name' => $info['name'],
            'user_id' => $current_user_id,
        ];
        $mp_ids[$person_id] = true;
    }

    return [
        'alerts' => $results,
        'mp_count' => count($mp_ids),
        'registered' => $registered,
        'unregistered' => $unregistered,
    ];
}

/**
 * Prepare the email body text for a given user.
 */
function prepare_email_body(int $user_id, string $data): string {
    if ($user_id) {
        return "As a registered user, visit http://www.openaustralia.org/user/\nto unsubscribe from, or manage, your alerts.\n\n" . $data;
    }
    return "If you register on the site, you will be able to manage your\nalerts there as well as post comments. :)\n\n" . $data;
}

/**
 * Send email notification about gone MPs.
 */
function write_and_send_email($email, $user_id, $data, $nomail = true) {
    $data = prepare_email_body($user_id, $data);

    $out = "SEND: Sending email to $email\n";
    print $out;

    $d = ['to' => $email, 'template' => 'alert_mailout'];
    $m = ['DATA' => $data];
    if (!$nomail) {
        $success = send_template_email($d, $m);
        usleep(500000);
    } else {
        $success = true;
    }
    return $success;
}

// Main script execution (only when run directly, not when included)
if (realpath($argv[0] ?? '') === realpath(__FILE__)) {
    ini_set('memory_limit', -1);

    include '/data/vhost/staging.openaustralia.org/includes/easyparliament/init.php';
    include INCLUDESPATH . 'easyparliament/member.php';

    $LIVEALERTS = new ALERT();
    $alertdata = $LIVEALERTS->fetch(1, 0);
    $alertdata = $alertdata['data'];

    $result = find_gone_mp_alerts($alertdata);

    $current_email = '';
    $email_text = '';
    foreach ($result['alerts'] as $alert) {
        if ($alert['email'] !== $current_email) {
            if ($email_text) {
                print "$current_email : $email_text\n";
            }
            $current_email = $alert['email'];
            $email_text = '';
        }
        $email_text .= $alert['name'] . ', ';
    }
    if ($email_text) {
        print "$current_email : $email_text\n";
    }

    print "Number of different MPs: " . $result['mp_count'] . "\n";
    print "Email lookups: " . $result['registered'] . " registered, " . $result['unregistered'] . " unregistered\n";
}
