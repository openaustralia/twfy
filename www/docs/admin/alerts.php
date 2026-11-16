<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";
include_once __DIR__ . "/../../includes/easyparliament/searchengine.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\AlertStats;
use OpenAustralia\TWFY\Models\Alert as AlertModel;

$this_page = 'admin_alerts';

$PAGE->page_start();
$PAGE->stripe_start();

print '<h4>Statistics</h4>';
$PAGE->display_table(['header' => ['Stat', 'Number'], 'rows' => AlertStats::totals()]);

$order = ($_GET['o'] ?? '') == 'c' ? 'created' : 'email';
$describe = fn (string $criteria): string => (new SEARCHENGINE($criteria))->query_description_long();
$user_url = function (int $user_id): string {
    $USERURL = new URL('userview');
    $USERURL->insert(['u' => $user_id]);
    return $USERURL->generate();
};
$header = ['<a href="alerts.php">Email</a>', 'Criteria', '<a href="alerts.php?o=c">Created</a>'];

$lists = [
    'Active alerts' => AlertModel::active(),
    'Deleted alerts' => AlertModel::deletedAlerts(),
    'Unconfirmed alerts' => AlertModel::unconfirmedAlerts(),
];
foreach ($lists as $title => $alerts) {
    print "<h4>$title</h4>";
    $PAGE->display_table(['header' => $header, 'rows' => AlertStats::listRows($alerts, $order, $describe, $user_url)]);
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu
    ]
]);

$PAGE->page_end();
