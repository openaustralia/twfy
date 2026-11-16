<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\Models\Alert as AlertModel;

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'alerts', 'title' => 'Alert Statistics']);

$alerts = AlertModel::active()
  ->where('criteria', 'not like', '%speaker:%')
  ->where('criteria', 'like', '%,%')
  ->get(['alert_id', 'criteria']);
print '<h3>People who probably wanted separate signups</h3> <table>';
foreach ($alerts as $alert) {
    print "<tr><td>{$alert->alert_id}</td><td>{$alert->criteria}</td></tr>";
}
print '</table>';

$counts = AlertModel::active()
  ->where('criteria', 'like', 'speaker:%')
  ->select('criteria')
  ->selectRaw('COUNT(*) AS c')
  ->groupBy('criteria')
  ->orderByDesc('c')
  ->get();
$tots = [];
$name = [];
foreach ($counts as $count) {
    $c = $count->c;
    $criteria = $count->criteria;
    if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) {
        continue;
    }
    $person_id = $m[1];
    $MEMBER = new MEMBER(['person_id' => $person_id]);
    if ($MEMBER->valid) {
        if (!array_key_exists($person_id, $tots)) {
            $tots[$person_id] = 0;
        }
        $tots[$person_id] += $c;
        $name[$person_id] = $MEMBER->full_name();
    }
}
$counts = AlertModel::where('confirmed', false)
  ->where('criteria', 'like', 'speaker:%')
  ->select('criteria')
  ->selectRaw('COUNT(*) AS c')
  ->groupBy('criteria')
  ->orderByDesc('c')
  ->get();
$unconfirmed = [];
foreach ($counts as $count) {
    $c = $count->c;
    $criteria = $count->criteria;
    if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) {
        continue;
    }
    $person_id = $m[1];
    if (isset($name[$person_id])) {
        if (!array_key_exists($person_id, $unconfirmed)) {
            $unconfirmed[$person_id] = 0;
        }
        $unconfirmed[$person_id] += $c;
    }
}
print '<h3>Alert signups by MP</h3> <table><tr><th>Name</th><th>Confirmed</th><th>Unconfirmed</th></tr> ';
foreach ($tots as $person_id => $c) {
    $u = $unconfirmed[$person_id] ?? 0;
    print "<tr><td>";
    print $name[$person_id];
    print "</td><td>$c</td><td>$u</td></tr>";
}
print '</table>';

$unconfirmed = [];
$confirmed = [];
$total = [];
$counts = AlertModel::active()
  ->where('criteria', 'not like', '%speaker:%')
  ->select('criteria')
  ->selectRaw('COUNT(*) AS c')
  ->groupBy('criteria')
  ->having('c', '>', 1)
  ->orderByDesc('c')
  ->get();
foreach ($counts as $count) {
    $confirmed[$count->criteria] = $count->c;
    $total[$count->criteria] = 1;
}
$counts = AlertModel::where('confirmed', false)
  ->where('criteria', 'not like', '%speaker:%')
  ->select('criteria')
  ->selectRaw('COUNT(*) AS c')
  ->groupBy('criteria')
  ->having('c', '>', 1)
  ->orderByDesc('c')
  ->get();
foreach ($counts as $count) {
    $unconfirmed[$count->criteria] = $count->c;
    $total[$count->criteria] = 1;
}
print '<h3>Alert signups by keyword</h3> <table><tr><th>Criteria</th><th>Confirmed</th><th>Unconfirmed</th></tr>';
foreach ($total as $criteria => $tot) {
    $c = $confirmed[$criteria] ?? 0;
    $u = $unconfirmed[$criteria] ?? 0;
    print "<tr><td>$criteria</td><td>$c</td><td>$u</tr>";
}
print '</table>';
$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
