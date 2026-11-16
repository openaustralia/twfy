<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\AlertStats;

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'alerts', 'title' => 'Alert Statistics']);

print '<h3>People who probably wanted separate signups</h3>';
print AlertStats::htmlTable([], AlertStats::probablySeparateSignupRows());

print '<h3>Alert signups by MP</h3>';
$name_for = function (int $person_id): ?string {
    $MEMBER = new MEMBER(['person_id' => $person_id]);
    return $MEMBER->valid ? $MEMBER->full_name() : null;
};
print AlertStats::htmlTable(['Name', 'Confirmed', 'Unconfirmed'], AlertStats::speakerRows($name_for));

print '<h3>Alert signups by keyword</h3>';
print AlertStats::htmlTable(['Criteria', 'Confirmed', 'Unconfirmed'], AlertStats::keywordRows());

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
