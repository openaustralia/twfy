<?php

/**
 * @file
 * Name: alertmailer.php
 * Description: Mailer for email alerts
 * $Id: alertmailer.php,v 1.20 2008/01/25 16:24:14 twfy-live Exp $
 */

/**
 *
 */
function mlog($message) {
    print $message;
}

include '../www/includes/easyparliament//init.php';
ini_set('memory_limit', -1);
include_once INCLUDESPATH . 'easyparliament/member.php';

$global_start = getmicrotime();
$db = new ParlDB();

// Get current value of latest batch.
$q = $db->query('SELECT max(indexbatch_id) as max_batch_id FROM indexbatch');
$max_batch_id = $q->field(0, 'max_batch_id');
mlog("max_batch_id: " . $max_batch_id . "\n");

// Last sent is timestamp of last alerts gone out.
// Last batch is the search index batch number last alert went out to.
$lastsent = file('alerts-lastsent');
$lastupdated = trim($lastsent[0]);
if (!$lastupdated) {
    $lastupdated = strtotime('00:00 today');
}
$lastbatch = trim($lastsent[1]);
if (!$lastbatch) {
    $lastbatch = 0;
}
mlog("lastupdated: $lastupdated lastbatch: $lastbatch\n");

// Construct query fragment to select search index batches which
// have been made since last time we ran.
$batch_query_fragment = "";
for ($i = $lastbatch + 1; $i <= $max_batch_id; $i++) {
    $batch_query_fragment .= "batch:$i ";
}
$batch_query_fragment = trim($batch_query_fragment);
mlog("batch_query_fragment: " . $batch_query_fragment . "\n");

// For testing purposes, specify nomail on command line to not send out emails.
$nomail = FALSE;
$onlyemail = '';
$fromemail = '';
$toemail = '';
for ($k = 1; $k < $argc; $k++) {
    if ($argv[$k] == '--nomail') {
        $nomail = TRUE;
    }
    if (preg_match('#^--only=(.*)$#', $argv[$k], $m)) {
        $onlyemail = $m[1];
    }
    if (preg_match('#^--from=(.*)$#', $argv[$k], $m)) {
        $fromemail = $m[1];
    }
    if (preg_match('#^--to=(.*)$#', $argv[$k], $m)) {
        $toemail = $m[1];
    }
}

if ($nomail) {
    mlog("NOT SENDING EMAIL\n");
}
if (($fromemail && $onlyemail) || ($toemail && $onlyemail)) {
    mlog("Can't have both from/to and only!\n");
    exit;
}

$active = 0;
$queries = 0;
$unregistered = 0;
$registered = 0;
$sentemails = 0;

$LIVEALERTS = new ALERT();

$current_email = '';
$email_text = '';
$globalsuccess = 1;

// Fetch all confirmed, non-deleted alerts.
$confirmed = 1;
$deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

// Nothing debate specific, but has to be one of them.
$DEBATELIST = new DEBATELIST();

$sects = ['', 'House of Representatives debate', 'Westminster Hall debate', 'Written Answer', 'Written Ministerial Statement', 'Northern Ireland Assembly debate'];
$sects[101] = 'Senate debate';
$sects_short = ['', 'debate', 'westminhall', 'wrans', 'wms', 'ni'];
$sects_short[101] = 'senate';
$results = [];

$outof = count($alertdata);
$start_time = time();
foreach ($alertdata as $alertitem) {
    $active++;
    $email = $alertitem['email'];
    if ($onlyemail && $email != $onlyemail) {
        continue;
    }
    if ($fromemail && strtolower($email) <= $fromemail) {
        continue;
    }
    if ($toemail && strtolower($email) >= $toemail) {
        continue;
    }
    $criteria_raw = $alertitem['criteria'];
    $criteria_batch = $criteria_raw . " " . $batch_query_fragment;

    if ($email != $current_email) {
        if ($email_text) {
            write_and_send_email($current_email, $user_id, $email_text);
        }
        $current_email = $email;
        $email_text = '';
        $q = $db->query('SELECT user_id FROM users WHERE email = \'' . mysqli_real_escape_string($db, $email) . "'");
        if ($q->rows() > 0) {
            $user_id = $q->field(0, 'user_id');
            $registered++;
        }
        else {
            $user_id = 0;
            $unregistered++;
        }
        mlog("\nEMAIL: $email, uid $user_id; memory usage : " . memory_get_usage() . "\n");
    }

    $data = NULL;
    if (!isset($results[$criteria_batch])) {
        mlog("  ALERT $active/$outof QUERY $queries : Xapian query '$criteria_batch'");
        $start = getmicrotime();
        $SEARCHENGINE = new SEARCHENGINE($criteria_batch);
        // mlog("query_remade: " . $SEARCHENGINE->query_remade() . "\n");.
        $args = [
        // Note: use raw here for URLs, whereas search engine has batch.
        's' => $criteria_raw,
        // Return everything added since last time this script was run.
        'threshold' => $lastupdated,
        'o' => 'c',
        // This is limited to 1000 in hansardlist.php anyway.
        'num' => 1000,
        'pop' => 1,
        // Don't escape ampersands.
        'e' => 1
     ];
        $data = $DEBATELIST->_get_data_by_search($args);
        // Add to cache (but only for speaker queries, which are commonly repeated)
        if (preg_match('#^speaker:\d+$#', $criteria_raw, $m)) {
            mlog(", caching");
            $results[$criteria_batch] = $data;
        }
        // unset($SEARCHENGINE);
        $total_results = $data['info']['total_results'];
        $queries++;
        mlog(", hits " . $total_results . ", time " . (getmicrotime() - $start) . "\n");
    }
    else {
        mlog("  ACTION $active/$outof CACHE HIT : Using cached result for '$criteria_batch'\n");
        $data = $results[$criteria_batch];
    }

    if (isset($data['rows']) && count($data['rows']) > 0) {
        // Sort results into order, by major, then date, then hpos.
        usort($data['rows'], 'sort_by_stuff');
        $o = [];
        $major = 0;
        $count = [];
        $total = 0;
        $any_content = FALSE;
        foreach ($data['rows'] as $row) {
            if ($major != $row['major']) {
                $count[$major] = $total;
                $total = 0;
                $major = $row['major'];
                $o[$major] = '';
                $k = 3;
            }
            // mlog($row['major'] . " " . $row['gid'] ."\n");.
            if ($row['hdate'] < '2008-01-14') {
                continue;
            }
            $q = $db->query('SELECT gid_from FROM gidredirect WHERE gid_to=\'uk.org.publicwhip/' . $sects_short[$major] . '/' . mysqli_real_escape_string($db, $row['gid']) . "'");
            if ($q->rows() > 0) {
                continue;
            }
            --$k;
            if ($k >= 0) {
                $any_content = TRUE;
                $parentbody = str_replace(['&#8212;', '<span class="hi">', '</span>'], ['-', '*', '*'], $row['parent']['body']);
                $body = str_replace(['&#163;', '&#8212;', '<span class="hi">', '</span>'], ["\xa3", '-', '*', '*'], $row['body']);
                if (isset($row['speaker']) && count($row['speaker'])) {
                    $body = html_entity_decode(member_full_name($row['speaker']['house'], $row['speaker']['title'], $row['speaker']['first_name'], $row['speaker']['last_name'], $row['speaker']['constituency'])) . ': ' . $body;
                }

                $body = wordwrap($body, 72);
                $o[$major] .= $parentbody . ' (' . format_date($row['hdate'], SHORTDATEFORMAT) . ")\nhttp://www.openaustralia.org" . $row['listurl'] . "\n";
                $o[$major] .= $body . "\n\n";
            }
            $total++;
        }
        $count[$major] = $total;

        if ($any_content) {
            // Add data to email_text.
            $desc = trim(html_entity_decode($data['searchdescription']));
            $deschead = ucfirst(str_replace('containing ', '', $desc));
            foreach ($o as $major => $body) {
                if ($body) {
                    $heading = $deschead . ' : ' . $count[$major] . ' ' . $sects[$major] . ($count[$major] != 1 ? 's' : '');
                    $email_text .= "$heading\n" . str_repeat('=', strlen($heading)) . "\n\n";
                    if ($count[$major] > 3) {
                        $email_text .= "There are more results than we have shown here. See more:\nhttp://www.openaustralia.org/search/?s=" . urlencode($criteria_raw) . "+section:" . $sects_short[$major] . "&o=d\n\n";
                    }
                    $email_text .= $body;
                }
            }
            $email_text .= "To unsubscribe from your alert for items " . $desc . ", please use:\nhttp://www.openaustralia.org/D/" . $alertitem['alert_id'] . '-' . $alertitem['registrationtoken'] . "\n\n";
        }
    }
}
if ($email_text) {
    write_and_send_email($current_email, $user_id, $email_text);
}

mlog("\n");

$sss = "Active alerts: $active\nEmail lookups: $registered registered, $unregistered unregistered\nQuery lookups: $queries\nSent emails: $sentemails\n";
if ($globalsuccess) {
    $sss .= 'Everything went swimmingly, in ';
}
else {
    $sss .= 'Something went wrong! Total time: ';
}
$sss .= (getmicrotime() - $global_start) . "\n\n";
mlog($sss);
if (!$nomail && !$onlyemail) {
    $fp = fopen('alerts-lastsent', 'w');
    fwrite($fp, time() . "\n");
    fwrite($fp, $max_batch_id);
    fclose($fp);
    mail(ALERT_STATS_EMAILS, 'Email alert statistics', $sss, 'From: Email Alerts <' . ALERT_STATS_SENDER . '>');
}
mlog(date('r') . "\n");

/**
 *
 */
function sort_by_stuff($a, $b) {
    if ($a['major'] > $b['major']) {
        return 1;
    }
    if ($a['major'] < $b['major']) {
        return -1;
    }

    if ($a['hdate'] < $b['hdate']) {
        return 1;
    }
    if ($a['hdate'] > $b['hdate']) {
        return -1;
    }

    if ($a['hpos'] == $b['hpos']) {
        return 0;
    }
    return ($a['hpos'] > $b['hpos']) ? 1 : -1;
}

/**
 *
 */
function write_and_send_email($email, $user_id, $data) {
    global $globalsuccess, $sentemails, $nomail, $start_time;

    $data .= '====================' . "\n\n";
    if ($user_id) {
        $data .= "As a registered user, visit http://www.openaustralia.org/user/\nto unsubscribe from, or manage, your alerts.\n";
    }
    else {
        $data .= "If you register on the site, you will be able to manage your\nalerts there as well as post comments. :)\n";
    }
    $sentemails++;
    mlog("SEND $sentemails : Sending email to $email ... ");
    $d = ['to' => $email, 'template' => 'alert_mailout'];
    $m = ['DATA' => $data];
    if (!$nomail) {
        // True = "Precedence: bulk".
        $success = send_template_email($d, $m, TRUE);
        mlog("sent ... ");
        // Sleep if time between sending mails is less than a certain number of seconds on average.
        // Number of seconds per mail not to be quicker than.
        if (((time() - $start_time) / $sentemails) < 0.5) {
            mlog("pausing ... ");
            sleep(1);
        }
    }
    else {
        mlog($data);
        $success = 1;
    }
    mlog("done\n");
    if (!$success) {
        $globalsuccess = 0;
    }
}
