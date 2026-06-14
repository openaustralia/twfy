<?php

/**
 * @file
 */

use OpenAustralia\TWFY\Models\Commentreport;
use OpenAustralia\TWFY\Models\Comments;

include_once __DIR__ . "/../../includes//easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/commentreportlist.php";

$this_page = "admin_badusers";

$PAGE->page_start();

$PAGE->stripe_start();

?>
<h4>Users with lots of deleted comments</h4>
<?php

// Get a list of the users who have the most deleted comments.
$q = parlDBQuery("SELECT COUNT(*) AS deletedcount,
						u.user_id,
						u.firstname,
						u.lastname,
						u.email
				FROM 	comments c, users u
				WHERE 	c.visible = 0
				AND		c.user_id = u.user_id
				GROUP BY user_id
				ORDER BY deletedcount DESC");

$rows = [];
$USERURL = new URL('userview');

for ($row = 0; $row < $q->rows(); $row++) {

    $user_id = $q->field($row, 'user_id');

    // Get the total comments posted for this user.
    $totalcomments = Comments::where('user_id', $user_id)->count();

    $percentagedeleted = ($q->field($row, 'deletedcount') / $totalcomments) * 100;


    // Get complaints made about this user's comments, but not upheld.
    $notupheldcount = Comments::join('commentreports', 'commentreports.comment_id', '=', 'comments.comment_id')
      ->where('comments.user_id', $user_id)
      ->whereNotNull('commentreports.resolved')
      ->where('commentreports.upheld', '0')
      ->count();


    $USERURL->insert(['u' => $user_id]);

    $rows[] = [
        '<a href="' . $USERURL->generate() . '">' . $q->field($row, 'firstname') . ' ' . $q->field($row, 'lastname') . '</a>',
        $totalcomments,
        $q->field($row, 'deletedcount'),
        $percentagedeleted . '%',
        $notupheldcount
    ];
}

$tabledata = [
    'header' => [
        'Name',
        'Total comments',
        'Number deleted',
        'Percentage deleted',
        'Reports against not upheld'
    ],
    'rows' => $rows
];
$PAGE->display_table($tabledata);
?>
<h4>Users who've made most rejected reports</h4>
<?php
$rejectedReports = Commentreport::join('users as u', 'commentreports.user_id', '=', 'u.user_id')
  ->whereNotNull('commentreports.resolved')
  ->where('commentreports.upheld', '0')
  ->where('commentreports.user_id', '!=', 0)
  ->groupBy('commentreports.user_id')
  ->orderByDesc('rejectedcount')
  ->select(['commentreports.user_id', 'u.firstname', 'u.lastname'])
  ->selectRaw('COUNT(*) AS rejectedcount')
  ->get();

$rows = [];
$USERURL = new URL('userview');

foreach ($rejectedReports as $report) {

    $user_id = $report->user_id;

    $USERURL->insert(['u' => $user_id]);

    // Get how many valid complaints they've submitted.
    $upheldcount = Commentreport::where('user_id', $user_id)
      ->where('upheld', '1')
      ->count();

    $rows[] = [
        '<a href="' . $USERURL->generate() . '">' . $report->firstname . ' ' . $report->lastname . '</a>',
        $report->rejectedcount,
        $upheldcount
    ];

}
$tabledata = [
    'header' => [
        'Name',
        'Reports not upheld',
        'Reports upheld'
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
