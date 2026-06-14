<?php

/**
 * @file
 */

use OpenAustralia\TWFY\Models\User;

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

$this_page = "admin_home";

$PAGE->page_start();

$PAGE->stripe_start();

// General stats.

$PAGE->block_start(['title' => 'Stats']);

$confirmedusers = User::where('confirmed', 1)->count();

$unconfirmedusers = User::where('confirmed', 0)->count();

$olddate = gmdate("Y-m-d H:i:s", time() - 86400);
$dayusers = User::where('lastvisit', '>', $olddate)->count();

$olddate = gmdate("Y-m-d H:i:s", time() - 86400 * 7);
$weekusers = User::where('lastvisit', '>', $olddate)->count();
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

$recentUsers = User::orderBy('registrationtime', 'desc')
  ->limit(50)
  ->get(['firstname', 'lastname', 'email', 'user_id', 'confirmed', 'registrationtime']);

$rows = [];
$USERURL = new URL('userview');

foreach ($recentUsers as $user) {

    $user_id = $user->user_id;

    $USERURL->insert(['u' => $user_id]);

    if ($user->confirmed == 1) {
        $confirmed = 'Yes';
        $name = '<a href="' . $USERURL->generate() . '">' . htmlspecialchars($user->firstname)
        . ' ' . htmlspecialchars($user->lastname) . '</a>';
    } else {
        $confirmed = 'No';
        $name = htmlspecialchars($user->firstname . ' ' . $user->lastname);
    }

    $rows[] = [
        $name,
        '<a href="mailto:' . $user->email . '">' . $user->email . '</a>',
        $confirmed,
        $user->registrationtime
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
