<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";
include_once __DIR__ . "/../../includes/easyparliament/searchengine.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\Models\Alert as AlertModel;
use OpenAustralia\TWFY\Models\User as UserModel;

$this_page = 'admin_alerts';

$PAGE->page_start();
$PAGE->stripe_start();

print '<h4>Statistics</h4>';
$total = AlertModel::count();
$active = AlertModel::active()->count();
$deleted = AlertModel::where('deleted', true)->count();
$unconfirmed = AlertModel::where('confirmed', false)->count();
$rows = [['Total', $total], ['Active', $active], ['Deleted', $deleted], ['Unconfirmed', $unconfirmed]];
$tabledata = [
    'header' => ['Stat', 'Number'],
    'rows' => $rows
];
$PAGE->display_table($tabledata);

$order = 'email';
if (isset($_GET['o']) && $_GET['o'] == 'c') {
    $order = 'created';
}

print '<h4>Active alerts</h4>';
$alerts = AlertModel::active()->orderBy($order)->orderBy('alert_id')->get(['email', 'criteria', 'created']);
$tabledata = [
    'header' => ['<a href="alerts.php">Email</a>', 'Criteria', '<a href="alerts.php?o=c">Created</a>'],
    'rows' => generate_rows($alerts)
];
$PAGE->display_table($tabledata);

print '<h4>Deleted alerts</h4>';
$alerts = AlertModel::where('deleted', true)->orderBy($order)->orderBy('alert_id')->get(['email', 'criteria', 'created']);
$tabledata['rows'] = generate_rows($alerts);
$PAGE->display_table($tabledata);

print '<h4>Unconfirmed alerts</h4>';
$alerts = AlertModel::where('confirmed', false)->orderBy($order)->orderBy('alert_id')->get(['email', 'criteria', 'created']);
$tabledata['rows'] = generate_rows($alerts);
$PAGE->display_table($tabledata);

$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu
    ]
]);

$PAGE->page_end();

/**
 *
 */
function generate_rows($alerts) {
    $rows = [];
    $USERURL = new URL('userview');
    foreach ($alerts as $alert) {
        $email = $alert->email;
        $SEARCHENGINE = new SEARCHENGINE($alert->criteria);
        $user = UserModel::where('email', $email)->first(['user_id', 'firstname', 'lastname']);
        if ($user !== null) {
            $USERURL->insert(['u' => $user->user_id]);
            $name = '<a href="' . $USERURL->generate() . '">' . $user->firstname . ' ' . $user->lastname . '</a>';
        } else {
            $name = $email;
        }
        $created = $alert->created;
        if ($created == '0000-00-00 00:00:00') {
            $created = '&nbsp;';
        }
        $rows[] = [$name, $SEARCHENGINE->query_description_long(), $created];
    }
    return $rows;
}
