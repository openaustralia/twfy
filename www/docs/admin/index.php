<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

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

$q = parlDBQuery("SELECT firstname,
						lastname,
						email,
						user_id,
						confirmed,
						registrationtime
				FROM	users
				ORDER BY registrationtime DESC
				LIMIT 50
				");

$rows = [];
$USERURL = new URL('userview');

for ($row = 0; $row < $q->rows(); $row++) {

    $user_id = $q->field($row, 'user_id');

    $USERURL->insert(['u' => $user_id]);

    if ($q->field($row, 'confirmed') == 1) {
        $confirmed = 'Yes';
        $name = '<a href="' . $USERURL->generate() . '">' . htmlspecialchars($q->field($row, 'firstname'))
        . ' ' . htmlspecialchars($q->field($row, 'lastname')) . '</a>';
    } else {
        $confirmed = 'No';
        $name = htmlspecialchars($q->field($row, 'firstname') . ' ' . $q->field($row, 'lastname'));
    }

    $rows[] = [
        $name,
        '<a href="mailto:' . $q->field($row, 'email') . '">' . $q->field($row, 'email') . '</a>',
        $confirmed,
        $q->field($row, 'registrationtime')
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
