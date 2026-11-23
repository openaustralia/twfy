<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

use OpenAustralia\TWFY\Models\User;

$this_page = "admin_home";

$PAGE->page_start();

$PAGE->stripe_start();

// General stats.

$PAGE->block_start(['title' => 'Stats']);

$q = parlDBQuery("SELECT COUNT(*) AS count FROM users WHERE confirmed = '1'");
$confirmedusers = $q->field(0, 'count');

$q = parlDBQuery("SELECT COUNT(*) AS count FROM users WHERE confirmed = '0'");
$unconfirmedusers = $q->field(0, 'count');

$olddate = gmdate("Y-m-d H:i:s", time() - 86400);
$q = parlDBQuery("SELECT COUNT(*) AS count FROM users WHERE lastvisit > '$olddate'");
$dayusers = $q->field(0, 'count');

$olddate = gmdate("Y-m-d H:i:s", time() - 86400 * 7);
$q = parlDBQuery("SELECT COUNT(*) AS count FROM users WHERE lastvisit > '$olddate'");
$weekusers = $q->field(0, 'count');
?>
<ul>
<li>Confirmed users: <?php echo $confirmedusers; ?></li>
<li>Unconfirmed users: <?php echo $unconfirmedusers; ?></li>
<li>Logged-in users active in past day: <?php echo $dayusers; ?></li>
<li>Logged-in users active in past week: <?php echo $weekusers; ?></li>
</ul>

<?php
$PAGE->block_end();

// Recent users.

?>
<h4>Recently registered users</h4>
<?php

$recentUsers = User::query()
  ->select(['firstname', 'lastname', 'email', 'user_id', 'confirmed', 'registrationtime'])
  ->orderByDesc('registrationtime')
  ->limit(50)
  ->toBase()
  ->get();

$rows = [];
$USERURL = new URL('userview');

foreach ($recentUsers as $recentUser) {

    $USERURL->insert(['u' => $recentUser->user_id]);

    if ($recentUser->confirmed == 1) {
        $confirmed = 'Yes';
        $name = '<a href="' . $USERURL->generate() . '">' . htmlspecialchars($recentUser->firstname)
        . ' ' . htmlspecialchars($recentUser->lastname) . '</a>';
    } else {
        $confirmed = 'No';
        $name = htmlspecialchars($recentUser->firstname . ' ' . $recentUser->lastname);
    }

    $email = htmlspecialchars($recentUser->email);

    $rows[] = [
        $name,
        '<a href="mailto:' . $email . '">' . $email . '</a>',
        $confirmed,
        $recentUser->registrationtime
    ];
}

$tabledata = [
    'header' => [
        'Name',
        'Email',
        'Confirmed?',
        'Registration time'
    ],
    'rows' => $rows
];

$PAGE->display_table($tabledata);

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'        => 'html',
        'content'    => $menu
    ]
]);

$PAGE->page_end();
