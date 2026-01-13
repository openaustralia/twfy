<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/member.php";

// For displaying written answers.

if (get_http_var("d") != "") {
  // We have a date. so show all wrans on this day.

  $this_page = "wransday";

  $args = [
        'date' => get_http_var('d')
    ];

  $LIST = new WRANSLIST();

  $LIST->display('date', $args);


}
elseif (get_http_var("id") != "") {
  // We have an id so show that item.
  // Could be a section id or a q/a id.
  // Either way, we'll get a section heading and the q/as beneath it.

  $this_page = "wrans";
  $args = [
        'gid' => get_http_var('id'),
        // Search terms to be highlighted.
        's' => get_http_var('s'),
        // Member's speeches to be highlighted.
        'member_id' => get_http_var('m'),
        // Glossary is on by default.
        'glossarise' => 1
    ];

  if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm)) {
    $args['person_id'] = $mmm[1];
  }

  // Glossary can be turned off in the url.
  if (get_http_var('ug') == 1) {
    $args['glossarise'] = 0;
  }
  else {
    $args['sort'] = "regexp_replace";
    $GLOSSARY = new GLOSSARY($args);
  }

  $WRANSLIST = new WRANSLIST();

  $result = $WRANSLIST->display('gid', $args);
  // If it is a redirect, change URL.
  if (is_string($result)) {
    $URL = new URL('wrans');
    $URL->insert(['id' => $result]);
    header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
    exit;
  }

  $PAGE->stripe_start('side', 'comments');

  // Display all comments for this ep object.
  $COMMENTLIST = new COMMENTLIST();

  // For highlighting their comments.
  $args['user_id'] = get_http_var('u');
  $args['epobject_id'] = $WRANSLIST->epobject_id();

  $COMMENTLIST->display('ep', $args);

  $PAGE->stripe_end();








  // $TRACKBACK = new TRACKBACK;

  // $TRACKBACK->display('epobject_id', $commentdata);



}
elseif (get_http_var('y') != '') {

  // Show a calendar for a particular year's debates.

  // No date or wrans id. Show recent days with wrans on.

  $this_page = 'wransyear';

  if (is_numeric(get_http_var('y'))) {
    $pagetitle = $DATA->page_metadata($this_page, 'title');
    $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . get_http_var('y'));
  }

  $PAGE->page_start();

  $PAGE->stripe_start();

  $args = [
        'year' => get_http_var('y')
    ];

  $LIST = new WRANSLIST();

  $LIST->display('calendar', $args);

  $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => "wrans"
        ]
    ]);



}
elseif (get_http_var('pid')) {
  $this_page = "wransmp";
  $args = [
        'person_id' => get_http_var('pid'),
        'page' => get_http_var('p')
    ];
  $MEMBER = new MEMBER(['person_id' => $args['person_id']]);
  if ($MEMBER->valid) {
    $pagetitle = $DATA->page_metadata($this_page, 'title');
    $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . $MEMBER->full_name());
  }
  $LIST = new WRANSLIST();
  $LIST->display('mp', $args);
}
else {

  // No date or wrans id. Show recent days with wrans on.

  $this_page = "wransfront";

  $PAGE->page_start();

  $PAGE->stripe_start();
  ?>
    <h3>Some recent written answers</h3>
    <?php

    $WRANSLIST = new WRANSLIST();
    $WRANSLIST->display('recent_wrans', ['days' => 7, 'num' => 20]);

    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => 'calendar_wrans'
        ],
        [
            'type' => 'include',
            'content' => "wrans"
        ]
    ]);
}

$PAGE->page_end();
