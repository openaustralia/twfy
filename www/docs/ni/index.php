<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";

// For displaying all the NIA debates on a day, or a single debate.

if (get_http_var("d") != "") {
  $this_page = "nidebatesday";
  $args = [
        'date' => get_http_var('d')
    ];
  $LIST = new NILIST();
  $LIST->display('date', $args);

}
elseif (get_http_var('id') != "") {
  $this_page = "nidebates";
  $args = [
        'gid' => get_http_var('id'),
  // Search terms to be highlighted.
        's'    => get_http_var('s'),
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

  $LIST = new NILIST();

  $result = $LIST->display('gid', $args);
  // If it is a redirect, change URL.
  if (is_string($result)) {
    $URL = new URL('nidebates');
    $URL->insert(['id' => $result]);
    header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
    exit;
  }

}
elseif (get_http_var('y') != '') {

  $this_page = 'nidebatesyear';

  if (is_numeric(get_http_var('y'))) {
    $pagetitle = $DATA->page_metadata($this_page, 'title');
    $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . get_http_var('y'));
  }

  $PAGE->page_start();

  $PAGE->stripe_start();

  $args = [
        'year' => get_http_var('y')
    ];

  $LIST = new NILIST();

  $LIST->display('calendar', $args);

  $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => "nidebates"
        ]
    ]);

}
elseif (get_http_var('gid') != '') {
  $this_page = 'nidebate';
  $args = ['gid' => get_http_var('gid')];
  $NILIST = new NILIST();
  $result = $NILIST->display('gid', $args);
  // If it is a redirect, change URL.
  if (is_string($result)) {
    $URL = new URL('nidebate');
    $URL->insert(['gid' => $result]);
    header('Location: http://' . DOMAIN . $URL->generate('none'));
    exit;
  }
  if ($NILIST->htype() == '12' || $NILIST->htype() == '13') {
    $PAGE->stripe_start('side', 'comments');
    $COMMENTLIST = new COMMENTLIST();
    $args['user_id'] = get_http_var('u');
    $args['epobject_id'] = $NILIST->epobject_id();
    $COMMENTLIST->display('ep', $args);
    $PAGE->stripe_end();
  }
}
else {
  $this_page = "nidebatesfront";
  $PAGE->page_start();
  $PAGE->stripe_start();
  ?>
                <h4>Busiest debates from the most recent week</h4>
  <?php

  $LIST = new NILIST();
  $LIST->display('biggest_debates', ['days' => 7, 'num' => 20]);

  $rssurl = $DATA->page_metadata($this_page, 'rss');
  $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => 'calendar_nidebates'
        ],
        [
            'type' => 'include',
            'content' => "nidebates"
        ],
        [
            'type' => 'html',
            'content' => '<div class="block"><h4><a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></h4></div>'
        ]
    ]);

}


$PAGE->page_end();

twfy_debug_timestamp("page end");
