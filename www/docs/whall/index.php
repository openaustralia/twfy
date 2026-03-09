<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/glossary.php";


// For displaying all the WH debates on a day, or a single WH debate.


if (get_http_var("d") != "") {
    // We have a date. so show all WH debates on this day.

    $this_page = "whallday";

    $args = [
        'date' => get_http_var('d')
    ];

    $LIST = new WHALLLIST();

    $LIST->display('date', $args);


}
elseif (get_http_var('id') != "") {
    // We have an id so show that item.
    // Could be a section id (so we get a list of all the subsections in it),
    // or a subsection id (so we'd get the whole debate),
    // or an item id within a debate in which case we just get that item and some headings.

    $this_page = 'whalls';

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


    $LIST = new WHALLLIST();

    $result = $LIST->display('gid', $args);
    // If it is a redirect, change URL.
    if (is_string($result)) {
        $URL = new URL('whall');
        $URL->insert(['id' => $result]);
        header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
        exit;
    }


    // We show trackbacks on this page.

    $args = [
        'epobject_id' => $LIST->epobject_id()
    ];

    // $TRACKBACK = new TRACKBACK;

    // $TRACKBACK->display('epobject_id', $args);



}
elseif (get_http_var('y') != '') {

    // Show a calendar for a particular year's WH debates.

    $this_page = 'whallyear';

    if (is_numeric(get_http_var('y'))) {
        $pagetitle = $DATA->page_metadata($this_page, 'title');
        $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . get_http_var('y'));
    }

    $PAGE->page_start();

    $PAGE->stripe_start();

    $args = [
        'year' => get_http_var('y')
    ];

    $LIST = new WHALLLIST();

    $LIST->display('calendar', $args);


    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => "whalldebates"
        ]
    ]);
}
elseif (get_http_var('gid') != '') {
    $this_page = 'whall';
    $args = ['gid' => get_http_var('gid')];
    $WHALLLIST = new WHALLLIST();
    $result = $WHALLLIST->display('gid', $args);
    // If it is a redirect, change URL.
    if (is_string($result)) {
        $URL = new URL('whall');
        $URL->insert(['gid' => $result]);
        header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
        exit;
    }
    if ($WHALLLIST->htype() == '12' || $WHALLLIST->htype() == '13') {
        $PAGE->stripe_start('side', 'comments');
        $COMMENTLIST = new COMMENTLIST();
        $args['user_id'] = get_http_var('u');
        $args['epobject_id'] = $WHALLLIST->epobject_id();
        $COMMENTLIST->display('ep', $args);
        $PAGE->stripe_end();
        $TRACKBACK = new TRACKBACK();
        $TRACKBACK->display('epobject_id', $commendata);
    }
}
else {
    // No date or debate id. Show recent years with debates on.

    $this_page = "whallfront";

    $PAGE->page_start();

    $PAGE->stripe_start();
    ?>
    <h4>Busiest Westminster Hall debates from the most recent week</h4>
    <?php

    $WHALLLIST = new WHALLLIST();
    $WHALLLIST->display('biggest_debates', ['days' => 7, 'num' => 20]);

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => 'calendar_whalldebates'
        ],
        [
            'type' => 'include',
            'content' => "whalldebates"
        ],
        [
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.openaustralia.org/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
</div>'
        ]
    ]);

}


$PAGE->page_end();
