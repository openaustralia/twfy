<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";

// For displaying all the SP debates on a day, or a single debate.

if (get_http_var("d") != "") {
    $this_page = "spdebatesday";
    $args = [
        'date' => get_http_var('d')
    ];
    $LIST = new SPLIST();
    $LIST->display('date', $args);

} elseif (get_http_var('id') != "") {
    $this_page = "spdebates";
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
    } else {
        $args['sort'] = "regexp_replace";
        $GLOSSARY = new GLOSSARY($args);
    }

    $LIST = new SPLIST();

    $result = $LIST->display('gid', $args);
    // If it is a redirect, change URL.
    if (is_string($result)) {
        $URL = new URL('spdebates');
        $URL->insert(['id' => $result]);
        header('Location: http://' . DOMAIN . $URL->generate('none'), TRUE, 301);
        exit;
    }

} elseif (get_http_var('y') != '') {

    $this_page = 'spdebatesyear';

    if (is_numeric(get_http_var('y'))) {
        $pagetitle = $DATA->page_metadata($this_page, 'title');
        $DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . get_http_var('y'));
    }

    $PAGE->page_start();

    $PAGE->stripe_start();

    $args = [
        'year' => get_http_var('y')
    ];

    $LIST = new SPLIST();

    $LIST->display('calendar', $args);

    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => "spdebates"
        ]
    ]);

} elseif (get_http_var('gid') != '') {
    $this_page = 'spdebate';
    $args = ['gid' => get_http_var('gid')];
    $LIST = new SPLIST();
    $result = $LIST->display('gid', $args);
    // If it is a redirect, change URL.
    if (is_string($result)) {
        $URL = new URL('spdebate');
        $URL->insert(['gid' => $result]);
        header('Location: http://' . DOMAIN . $URL->generate('none'));
        exit;
    }
    if ($LIST->htype() == '12' || $LIST->htype() == '13') {
        $PAGE->stripe_start('side', 'comments');
        $COMMENTLIST = new COMMENTLIST();
        $args['user_id'] = get_http_var('u');
        $args['epobject_id'] = $LIST->epobject_id();
        $COMMENTLIST->display('ep', $args);
        $PAGE->stripe_end();
    }
} else {
    $this_page = "spdebatesfront";
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>
    <h4>Busiest debates from the most recent week</h4>
    <?php

    $LIST = new SPLIST();
    $LIST->display('biggest_debates', ['days' => 7, 'num' => 20]);

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
        [
            'type' => 'include',
            'content' => 'calendar_spdebates'
        ],
        [
            'type' => 'include',
            'content' => "spdebates"
        ],
        [
            'type' => 'html',
            'content' => '<div class="block"><h4><a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></h4></div>'
        ]
    ]);

}


$PAGE->page_end();

twfy_debug_timestamp("page end");
