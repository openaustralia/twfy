<?php
$_SERVER['DEVICE_TYPE'] = "mobile";

include_once __DIR__ . '/../../includes/easyparliament/init.php';
include_once __DIR__ . '/../../includes/easyparliament/member.php';
include_once __DIR__ . '/../../includes/easyparliament/glossary.php';
include_once __DIR__ . '/../../includes/easyparliament/search.php';

// From http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
include_once __DIR__ . '/../../includes/postcode.php';

if (get_http_var('s') != '' || get_http_var('pid') != '') {
    // We're searching for something.

    $this_page = 'search';

    $searchstring = trim(get_http_var('s'));
    $searchstring = filter_user_input($searchstring, 'strict');

    $time = parse_date($searchstring);
    if ($time['iso']) {
        header('Location: /hansard/?d=' . $time['iso']);
        exit;
    }

    $searchspeaker = trim(get_http_var('pid'));
    if ($searchspeaker) {
        $searchstring .= ($searchstring ? ' ' : '') . 'speaker:' . $searchspeaker;
    }
    $searchmajor = trim(get_http_var('section'));
    if (!$searchmajor) {
        // Legacy URLs used maj
        $searchmajor = trim(get_http_var('maj'));
    }
    if ($searchmajor) {
        $searchstring .= " section:$searchmajor";
    }
    $searchgroupby = trim(get_http_var('groupby'));
    if ($searchgroupby) {
        $searchstring .= " groupby:$searchgroupby";
    }

    // We have only one of these, rather than one in HANSARDLIST also
    global $SEARCHENGINE;

    if (get_http_var('o') == 'p') {
        $q_house = '';
        if (ctype_digit(get_http_var('house'))) {
            $q_house = get_http_var('house');
        }

        # Fetch the results
        $data = search_by_usage($searchstring, $q_house);


        $pagetitle = 'Who says ' . $data['pagetitle'] . ' the most?';
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $PAGE->page_start_mobile();
        $PAGE->stripe_start();
        $PAGE->search_form();
        if (isset($data['error'])) {
            print '<p>' . $data['error'] . '</p>';
            $PAGE->page_end_mobile();
            return;
        }

        if (isset($data['limit_reached'])) {
            print '<p><em>This service runs on a maximum number of 10,000 results, to conserve memory</em></p>';
        }
        print "\n\n<!-- ";
        foreach ($data['party_count'] as $party => $count) {
            print "$party:$count<br>";
        }
        print " -->\n\n";
        ?>
        <p>Please note that this search is only for the exact word/phrase entered.
            For example, putting in 'autism' won't return results for 'autistic spectrum disorder',
            you will have to search for it separately.</p>
        <table>
            <tr>
                <th>Number of occurences</th>
                <th>
                    <?php


                    $URL = new URL($this_page);
                    $url_l = $URL->generate('html', ['house' => HOUSE::SENATE]);
                    $url_c = $URL->generate('html', ['house' => HOUSE::REPRESENTATIVES]);
                    $URL->remove(['house']);
                    $url_b = $URL->generate();
                    switch ($q_house) {
                        case HOUSE::REPRESENTATIVES:
                            print "Representatives | <a href=\"$url_l\">Senators</a> | <a href=\"$url_b\">Both</a>";
                            break;
                        case HOUSE::SENATE:
                            print "<a href=\"$url_c\">Representatives</a> | Senators | <a href=\"$url_b\">Both</a>";
                            break;
                        default:
                            print "<a href=\"$url_c\">Representatives</a> | <a href=\"$url_l\">Senators</a> | Both";
                            break;
                    }
                    ?>
                </th>
                <th>Date range</th>
            </tr>
            <?php
            foreach ($data['speakers'] as $pid => $speaker) {
                print '<tr><td align="center">';
                print $speaker['count'] . '</td><td>';
                if ($pid) {
                    $house = $speaker['house'];
                    $left = $speaker['left'];
                    if ($house == HOUSE::REPRESENTATIVES) {
                        print '<span style="color:#009900">&bull;</span> ';
                    } elseif ($house == HOUSE::SENATE) {
                        print '<span style="color:#990000">&bull;</span> ';
                    }
                    if ($left == '9999-12-31') {
                        print '<a href="' . WEBPATH . 'search/?s=' . urlencode($searchstring) . '&amp;pid=' . $pid . '">';
                    }
                }
                print htmlentities($speaker['name']);
                if ($pid) {
                    print '</a>';
                }
                if ($speaker['party']) {
                    print ' (' . htmlentities($speaker['party']) . ')';
                }
                if (isset($speaker['office'])) {
                    print ' - ' . htmlentities(join('; ', $speaker['office']));
                }
                print '</td> <td>';
                $pmindate = $speaker['pmindate'];
                $pmaxdate = $speaker['pmaxdate'];
                if (format_date($pmindate, 'M Y') == format_date($pmaxdate, 'M Y')) {
                    print format_date($pmindate, 'M Y');
                } else {
                    print str_replace(' ', '&nbsp;', format_date($pmindate, 'M Y') . ' &ndash; ' . format_date($pmaxdate, 'M Y'));
                }
                print '</td></tr>';
            }
            print '</table>';

    } else {
        $SEARCHENGINE = new SEARCHENGINE($searchstring);
        $pagetitle = "Search: " . $SEARCHENGINE->query_description_short();
        $pagenum = get_http_var('p');
        if (is_numeric($pagenum) && $pagenum > 1) {
            $pagetitle .= " page $pagenum";
        }

        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $DATA->set_page_metadata($this_page, 'rss', 'search/rss/?s=' . urlencode($searchstring));
        $PAGE->page_start_mobile();
        $PAGE->stripe_start();
        $PAGE->search_form();

        $o = get_http_var('o');
        $args = [
            's' => $searchstring,
            'p' => $pagenum,
            'num' => get_http_var('num'),
            'pop' => get_http_var('pop'),
            'o' => ($o == 'd' || $o == 'r') ? $o : 'd',
        ];

        $LIST = new HANSARDLIST();

        if ($args['s']) {
            find_members($args);
        }

        $LIST->display('search', $args);

        if ($args['s']) {
            find_constituency($args);
            find_glossary_items($args);
        }
    }
} else {
    // No search term. Display help.
    $this_page = 'search_help';
    $PAGE->page_start_mobile();
    $PAGE->stripe_start();
    include __DIR__ . '/../../includes/easyparliament/staticpages/search_help.php';
}


$PAGE->page_end_mobile();

