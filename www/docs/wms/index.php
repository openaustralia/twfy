<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/glossary.php";


// For displaying all the WMS on a day, or a single WMS.

if (get_http_var("d") != "") {
  // We have a date. so show all WMS on this day.
  $this_page = "wmsday";
  $args = [
        'date' => get_http_var('d')
    ];
  $LIST = new WMSLIST();
  $LIST->display('date', $args);

}
elseif (get_http_var('y') != '') {

  // Show a calendar for a particular year's WMS.

  $this_page = 'wmsyear';

  if (is_numeric(get_http_var('y'))) {
    $pagetitle = $DATA->page_metadata($this_page, 'title');
    $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . get_http_var('y'));
  }

  $PAGE->page_start();

  $PAGE->stripe_start();

  $args = [
        'year' => get_http_var('y')
    ];

  $LIST = new WMSLIST();

  $LIST->display('calendar', $args);


  $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => "wms"
        ]
    ]);
}
elseif (get_http_var('id') != '') {
  $this_page = 'wms';
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


  $WMSLIST = new WMSLIST();

  $result = $WMSLIST->display('gid', $args);
  if (is_string($result)) {
    $URL = new URL('wms');
    $URL->insert(['id' => $result]);
    header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
    exit;
  }

  $PAGE->stripe_start('side', 'comments');
  $COMMENTLIST = new COMMENTLIST();
  $args['user_id'] = get_http_var('u');
  $args['epobject_id'] = $WMSLIST->epobject_id();
  $COMMENTLIST->display('ep', $args);
  $PAGE->stripe_end();
  // $TRACKBACK = new TRACKBACK;
  // $TRACKBACK->display('epobject_id', $commendata);
}
else {
  // No date or debate id. Show recent WMS.

  $this_page = "wmsfront";

  $PAGE->page_start();

  $PAGE->stripe_start();
  ?>
                <h4>Some recent written ministerial statements</h4>
  <?php

  $WMSLIST = new WMSLIST();
  $WMSLIST->display('recent_wms', ['days' => 7, 'num' => 20]);

  $rssurl = $DATA->page_metadata($this_page, 'rss');
  $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => 'calendar_wms'
        ],
        [
            'type' => 'include',
            'content' => "wms"
        ],
        [
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img border="0" alt="RSS feed" align="middle" src="http://www.openaustralia.org/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of recent statements</a></p>
</div>'

        ]
    ]);

}


$PAGE->page_end();
