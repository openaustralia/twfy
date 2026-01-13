<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/glossary.php";

// For displaying Standing Committee debates. I know they're Public Bill
// Committees now, but I've called them standing committees everywhere.

$bill = get_http_var('bill');
$session = get_http_var('session');
$id = get_http_var('id');

$bill_id = NULL;
if ($bill && $session) {
  $db = new ParlDB();
  $q = $db->query('select id,standingprefix from bills where title="' . mysqli_real_escape_string($db, $bill) . '"
		and session = "' . mysqli_real_escape_string($db, $session) . '"');
  if ($q->rows()) {
    $bill_id = $q->field(0, 'id');
    $standingprefix = $q->field(0, 'standingprefix');
  }
}

$committee = new StandingCommittee($session, $bill);

if ($bill_id && !$id) {
  $this_page = 'pbc_bill';
  $args = [
        'id' => $bill_id,
        'title' => $bill,
        'session' => $session,
    ];
  $committee->display('bill', $args);
}
elseif ($bill_id && $id) {
  $this_page = 'pbc_clause';
  $args = [
        'gid' => $standingprefix . $id,
        's' => get_http_var('s'),
        'member_id' => get_http_var('m'),
        'glossarise' => 1,
        'sort' => 'regexp_replace',
        'bill_id' => $bill_id,
        'bill_title' => $bill,
        'bill_session' => $session,
    ];
  // Why a global?
  $GLOSSARY = new GLOSSARY($args);

  if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm)) {
    $args['person_id'] = $mmm[1];
  }

  $result = $committee->display('gid', $args);
  /* This section below is shared between here and everywhere else - factor it out! */
  if ($committee->htype() == '12' || $committee->htype() == '13') {
    $PAGE->stripe_start('side', 'comments');
    $COMMENTLIST = new COMMENTLIST();
    $args['user_id'] = get_http_var('u');
    $args['epobject_id'] = $committee->epobject_id();
    $COMMENTLIST->display('ep', $args);
    $PAGE->stripe_end();
  }
}
elseif ($session) {
  $this_page = 'pbc_session';
  $DATA->set_page_metadata($this_page, 'title', "Session $session");
  $args = [
        'session' => $session,
    ];
  $committee->display('session', $args);
}
else {
  $this_page = "pbc_front";
  $PAGE->page_start();
  $PAGE->stripe_start();
  ?>
    <h4>Most recent Public Bill committee debates</h4>
    <p><a href="2006-07/">See all committees for the current session</a></p>
    <?php

    $committee->display('recent_debates', ['num' => 20]);
    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end([
        [
            'type' => 'include',
            'content' => "pbc"
        ],
        [
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.openaustralia.org/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent committee debates</a></p>
</div>'
        ]
    ]);

}

$PAGE->page_end();
