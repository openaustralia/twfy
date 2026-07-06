<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";
include_once __DIR__ . "/../../includes/easyparliament/searchengine.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\User as UserModel;

$this_page = 'admin_alerts';

$PAGE->page_start();
$PAGE->stripe_start();

print '<h4>Statistics</h4>';
$total = Alert::query()->count();
$active = Alert::query()->where('confirmed', 1)->where('deleted', 0)->count();
$deleted = Alert::query()->where('deleted', 1)->count();
$unconfirmed = Alert::query()->where('confirmed', 0)->count();
$rows = [['Total', $total], ['Active', $active], ['Deleted', $deleted], ['Unconfirmed', $unconfirmed]];
$tabledata = [
    'header' => ['Stat', 'Number'],
    'rows' => $rows
];
$PAGE->display_table($tabledata);

$orderByCreated = isset($_GET['o']) && $_GET['o'] == 'c';

print '<h4>Active alerts</h4>';
$q = Alert::query()
  ->select(['email', 'criteria', 'created'])
  ->where('confirmed', 1)
  ->where('deleted', 0);
if ($orderByCreated) {
    $q->orderBy('created')->orderBy('alert_id');
} else {
    $q->orderBy('email')->orderBy('alert_id');
}
$tabledata = [
    'header' => ['<a href="alerts.php">Email</a>', 'Criteria', '<a href="alerts.php?o=c">Created</a>'],
    'rows' => generate_rows($q->get())
];
$PAGE->display_table($tabledata);

print '<h4>Deleted alerts</h4>';
$q = Alert::query()
  ->select(['email', 'criteria', 'created'])
  ->where('deleted', 1);
if ($orderByCreated) {
    $q->orderBy('created')->orderBy('alert_id');
} else {
    $q->orderBy('email')->orderBy('alert_id');
}
$tabledata['rows'] = generate_rows($q->get());
$PAGE->display_table($tabledata);

print '<h4>Unconfirmed alerts</h4>';
$q = Alert::query()
  ->select(['email', 'criteria', 'created'])
  ->where('confirmed', 0);
if ($orderByCreated) {
    $q->orderBy('created')->orderBy('alert_id');
} else {
    $q->orderBy('email')->orderBy('alert_id');
}
$tabledata['rows'] = generate_rows($q->get());
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
function generate_rows(Collection $alerts): array {
    $rows = [];
    $USERURL = new URL('userview');

    if ($alerts->isEmpty()) {
        return $rows;
    }

    $usersByEmail = UserModel::query()
      ->whereIn('email', $alerts->pluck('email')->unique()->all())
      ->get(['user_id', 'firstname', 'lastname', 'email'])
      ->keyBy('email');

    foreach ($alerts as $alert) {
        $email = $alert->email;
        $criteria = $alert->criteria;
        $SEARCHENGINE = new SEARCHENGINE($criteria);

        $user = $usersByEmail->get($email);
        if ($user) {
            $user_id = $user->user_id;
            $USERURL->insert(['u' => $user_id]);
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
