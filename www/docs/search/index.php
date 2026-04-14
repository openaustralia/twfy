<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";

// From http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
include_once INCLUDESPATH . "postcode.inc";

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

        $pagetitle = 'Who says ' . htmlentities($data['pagetitle']) . ' the most?';
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $PAGE->page_start();
        $PAGE->stripe_start();
        $PAGE->search_form();
        if (isset($data['error'])) {
            print '<p>' . htmlentities($data['error']) . '</p>';
            $PAGE->page_end();
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
                    $url_l = $URL->generate('html', ['house' => 2]);
                    $url_c = $URL->generate('html', ['house' => 1]);
                    $URL->remove(['house']);
                    $url_b = $URL->generate();
                    switch ($q_house) {
                        case 1:
                            print "Representatives | <a href=\"$url_l\">Senators</a> | <a href=\"$url_b\">Both</a>";
                            break;
                        case 2:
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
            <?
            foreach ($data['speakers'] as $pid => $speaker) {
                print '<tr><td align="center">';
                print $speaker['count'] . '</td><td>';
                if ($pid) {
                    $house = $speaker['house'];
                    $left = $speaker['left'];
                    if ($house == 1) {
                        print '<span style="color:#009900">&bull;</span> ';
                    } elseif ($house == 2) {
                        print '<span style="color:#990000">&bull;</span> ';
                    }
                    if ($left == '9999-12-31') {
                        print '<a href="' . WEBPATH . 'search/?s=' . urlencode($searchstring) . '&amp;pid=' . $pid;
                    }
                    if ($left == '9999-12-31') {
                        print '">';
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
        $PAGE->page_start();
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
            find_comments($args);
        }
    }
} else {
    // No search term. Display help.
    $this_page = 'search_help';
    $PAGE->page_start();
    $PAGE->stripe_start();
    include INCLUDESPATH . 'easyparliament/staticpages/search_help.php';
}

$PAGE->stripe_end(array(
    [
        'type' => 'include',
        'content' => 'search_links'
    ],
    [
        'type' => 'include',
        'content' => 'search'
    ]
));
$PAGE->page_end();

function find_comments($args){
    global $PAGE, $db;
    $commentlist = new COMMENTLIST;
    $commentlist->display('search', $args);
}

function find_constituency($args){
    // We see if the user is searching for a postcode or constituency.
    global $PAGE, $db;

    if ($args['s'] != '') {
        $searchterm = $args['s'];
    } else {
        $PAGE->error_message('No search string');
        return false;
    }

    $constituencies = [];
    $constituency = '';
    $validpostcode = false;

    if (validate_postcode($searchterm)) {
        // Looks like a postcode - can we find the constituency?
        $constituencies = postcode_to_constituency($searchterm);
        if ($constituencies == '') {
            $constituencies = [];
        } else {
            $validpostcode = true;
        }
        if (!is_array($constituencies)) {
            $constituencies = [$constituencies];
        }
    }

    if ($constituencies == [] && $searchterm) {
        // No luck so far - let's see if they're searching for a constituency.
        $try = strtolower($searchterm);
        if (normalise_constituency_name($try)) {
            $constituency = normalise_constituency_name($try);
        } else {
            $q = $db->query("SELECT DISTINCT
                    (SELECT name FROM constituency WHERE cons_id = o.cons_id AND main_name) AS name
                FROM constituency AS o WHERE name LIKE ?
                AND from_date <= DATE(NOW()) AND DATE(NOW()) <= to_date",
                "%$try%"
            );
            for ($n = 0; $n < $q->rows(); $n++) {
                $constituencies[] = $q->field($n, 'name');
            }
        }
    }

    if (count($constituencies) == 1) {
        $constituency = $constituencies[0];
    }

    if ($constituency != '') {
        // Got a match, display....

        $MEMBER = new MEMBER(['constituency' => $constituency]);
        $URL = new URL('mp');
        if ($MEMBER->valid) {
            $URL->insert(['m' => $MEMBER->member_id()]);
            print '<h3>MP for ' . preg_replace("#$searchterm#i", '<span class="hi">$0</span>', $constituency);
            if ($validpostcode) {
                // Display the postcode the user searched for.
                print ' (' . htmlentities(strtoupper($args['s'])) . ')';
            }
            print "</h2>";
            print "<p><a href=\"" . $URL->generate() . "\"><strong>" . htmlentities($MEMBER->first_name()) . ' ' . htmlentities($MEMBER->last_name()) . "</strong></a>
                    (" . htmlentities($MEMBER->party()) . ")</p>";
        }

    } elseif (count($constituencies)) {
        print "<h3>MPs in constituencies matching '" . htmlentities($searchterm) . "'</h3><ul>";
        foreach ($constituencies as $constituency) {
            $MEMBER = new MEMBER(['constituency' => $constituency]);
            $URL = new URL('mp');
            if ($MEMBER->valid) {
                $URL->insert(['m' => $MEMBER->member_id()]);
            }
            print '<li><a href="' . $URL->generate() . '"><strong>' . htmlentities($MEMBER->first_name()) . ' ' . htmlentities($MEMBER->last_name()) . '</strong></a>';
            print '(' . preg_replace("#$searchterm#i", '<span class="hi">$0</span>', $constituency) . ', ' . htmlentities($MEMBER->party()) . ')</li>';
        }
        print '</ul>';
    }
}

function find_members($args){
    // Maybe there'll be a better place to put this at some point...
    global $PAGE, $db, $parties;

    if ($args['s'] != '') {
        // $args['s'] should have been tidied up by the time we get here.
        // eg, by doing filter_user_input($s, 'strict');
        $searchstring = $args['s'];
    } else {
        $PAGE->error_message("No search string");
        return false;
    }

    $searchwords = explode(' ', preg_replace('#[^a-z ]#i', '', $searchstring));
    foreach ($searchwords as $i => $searchword) {
        $searchwords[$i] = htmlentities($searchword);
        if (!strcasecmp($searchword, 'Opik'))
            $searchwords[$i] = '&Ouml;pik';
    }

    $params = [];
    if (count($searchwords) == 1) {
        $where = "first_name LIKE ? OR last_name LIKE ?";
        $params = ["%$searchwords[0]%", "%$searchwords[0]%"];
    } elseif (count($searchwords) == 2) {
        // We don't do anything special if there are more than two search words.
        // And here we're assuming the user's put the names in the right order.
        $where = "(first_name LIKE ? AND last_name LIKE ?)";
        $where .= " OR (first_name LIKE ? AND last_name LIKE ?)";
        $params = [
            "%$searchwords[0]%",
            "%$searchwords[1]%",
            "%$searchwords[1]%",
            "%$searchwords[0]%",
        ];
    } else {
        $where = "(first_name LIKE ? AND last_name LIKE ?)";
        $where .= " OR (first_name LIKE ? AND last_name LIKE ?)";
        $params = [
            "%$searchwords[0] $searchwords[1]%",
            "%$searchwords[2]%",
            "%$searchwords[0]%",
            "%$searchwords[1] $searchwords[2]%",
        ];
    }

    $q = $db->query("SELECT person_id,
                            title, first_name, last_name,
                            constituency, party,
                            left_house, house
                    FROM 	member
                    WHERE	($where)
                    ORDER BY last_name, first_name, person_id, entered_house desc
                    ", ...$params);

    if ($q->rows() > 0) {

        $URL1 = new URL('mp');
        $URL2 = new URL('peer');
        $members = [];

        $last_pid = -1;
        for ($n = 0; $n < $q->rows(); $n++) {
            if ($q->field($n, 'person_id') != $last_pid) {
                $last_pid = $q->field($n, 'person_id');
                if ($q->field($n, 'left_house') != '9999-12-31') {
                    $former = 'formerly ';
                } else {
                    $former = '';
                }
                if ($q->field($n, 'house') == 1) {
                    $URL1->insert(array('pid' => $last_pid));
                    $s = '<a href="' . $URL1->generate() . '"><strong>';
                    $s .= $q->field($n, 'first_name') . ' ' . $q->field($n, 'last_name') . '</strong></a> (' . $former . $q->field($n, 'constituency') . ', ';
                } else {
                    $URL2->insert(array('pid' => $last_pid));
                    $s = '<a href="' . $URL2->generate() . '"><strong>';
                    $s .= member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency'));
                    $s .= '</strong></a> (';
                }
                $party = $q->field($n, 'party');
                if (isset($parties[$party]))
                    $party = $parties[$party];
                $s .= $party . ')';
                $MOREURL = new URL('search');
                $MOREURL->insert(array('pid' => $last_pid, 'pop' => 1, 's' => null));
                $s .= ' - <a href="' . $MOREURL->generate() . '">View recent appearances</a>';
                $members[] = $s;
            }
        }
        ?>
            <div id="people_results">
                <h3>Representatives matching '<?php echo htmlentities($searchstring); ?>'</h3>
                <ul>
                    <li><?php print implode("</li>\n\t<li>", array_map('htmlentities', $members)); ?></li>
                </ul>
            </div>
            <?php
    }

    // We don't display anything if there were no matches.

}

// Checks to see if the search term provided has any similar matching entries in the glossary.
// If it does, show links off to them.
function find_glossary_items($args){

    $searchterm = $args['s'];
    $GLOSSARY = new GLOSSARY($args);

    if (isset($GLOSSARY->num_search_matches) && $GLOSSARY->num_search_matches >= 1) {

        // Got a match(es), display....
        $URL = new URL('glossary');
        $URL->insert(['gl' => ""]);
        ?>
            <h3>Matching glossary terms:</h3>
            <p>
                <?php
                $n = 1;
                foreach ($GLOSSARY->search_matches as $glossary_id => $term) {
                    $URL->update(['gl' => $glossary_id]);
                    ?><a
                        href="<?php echo $URL->generate(); ?>"><strong><?php echo htmlentities($term['title']); ?></strong></a>
                    <?php
                    if ($n < $GLOSSARY->num_search_matches) {
                        print ", ";
                    }
                    $n++;
                }
                ?>
            </p>
            <?php
    }
}
