<?php

/**
 * Search helper functions for search page widgets.
 */

/**
 * Escape text and highlight occurrences of the search term.
 *
 * @param string $text Raw text to display.
 * @param string $searchterm User search term.
 *
 * @return string Escaped HTML with matched terms wrapped in highlight markup.
 */
function highlighted_html(string $text, string $searchterm): string {
    $escaped_text = htmlentities($text);
    if ($searchterm === '') {
        return $escaped_text;
    }

    $escaped_searchterm = htmlentities($searchterm);
    $pattern = '/' . preg_quote($escaped_searchterm, '/') . '/i';
    return preg_replace($pattern, '<span class="hi">$0</span>', $escaped_text);
}

/**
 * Render comment search results.
 *
 * @param array<string, mixed> $args Search arguments.
 */
function find_comments(array $args): void {
    $commentlist = new COMMENTLIST();
    $commentlist->display('search', $args);
}

/**
 * Render constituency/postcode matches.
 *
 * @param array<string, mixed> $args Search arguments.
 *
 * @return false|null Returns false on missing search input; otherwise null.
 */
function find_constituency(array $args) {
    // We see if the user is searching for a postcode or constituency.
    global $PAGE;
    $db = getParlDB();

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
            $q = $db->query(
                "SELECT DISTINCT
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
            print '<h3>MP for ' . highlighted_html($constituency, $searchterm);
            if ($validpostcode) {
                // Display the postcode the user searched for.
                print ' (' . htmlentities(strtoupper($args['s'])) . ')';
            }
            print '</h3>';
            print "<p><a href=\"" . htmlentities($URL->generate()) . "\"><strong>" . htmlentities($MEMBER->first_name()) . ' ' . htmlentities($MEMBER->last_name()) . "</strong></a>\n                    (" . htmlentities($MEMBER->party()) . ")</p>";
        }

    } elseif (count($constituencies)) {
        print "<h3>MPs in constituencies matching '" . htmlentities($searchterm) . "'</h3><ul>";
        foreach ($constituencies as $constituency) {
            $MEMBER = new MEMBER(['constituency' => $constituency]);
            $URL = new URL('mp');
            if ($MEMBER->valid) {
                $URL->insert(['m' => $MEMBER->member_id()]);
            }
            print '<li><a href="' . htmlentities($URL->generate()) . '"><strong>' . htmlentities($MEMBER->first_name()) . ' ' . htmlentities($MEMBER->last_name()) . '</strong></a>';
            print '(' . highlighted_html($constituency, $searchterm) . ', ' . htmlentities($MEMBER->party()) . ')</li>';
        }
        print '</ul>';
    }
}

/**
 * Render member matches for the search term.
 *
 * @param array<string, mixed> $args Search arguments.
 *
 * @return false|null Returns false on missing search input; otherwise null.
 */
function find_members(array $args) {
    // Maybe there'll be a better place to put this at some point...
    global $PAGE, $parties;
    $db = getParlDB();

    if ($args['s'] != '') {
        // $args['s'] should have been tidied up by the time we get here.
        // eg, by doing filter_user_input($s, 'strict');
        $searchstring = $args['s'];
    } else {
        $PAGE->error_message("No search string");
        return false;
    }

    $searchwords = explode(' ', preg_replace('#[^a-z ]#i', '', $searchstring));
    $params = [];

    // Clean up searchwords and handle special cases.
    $cleaned_words = [];
    foreach ($searchwords as $searchword) {
        $word = htmlentities($searchword);
        if (!strcasecmp($searchword, 'Opik')) {
            $word = '&Ouml;pik';
        }
        if (!empty($word)) {
            $cleaned_words[] = $word;
        }
    }
    $searchwords = $cleaned_words;

    if (count($searchwords) == 1) {
        $where = "first_name LIKE ? OR last_name LIKE ?";
        $params = ['%' . $searchwords[0] . '%', '%' . $searchwords[0] . '%'];
    } elseif (count($searchwords) == 2) {
        // We don't do anything special if there are more than two search words.
        // And here we're assuming the user's put the names in the right order.
        $where = "(first_name LIKE ? AND last_name LIKE ?) OR (first_name LIKE ? AND last_name LIKE ?)";
        $params = [
            "%$searchwords[0]%",
            "%$searchwords[1]%",
            "%$searchwords[1]%",
            "%$searchwords[0]%"
        ];
    } else {
        $where = "(first_name LIKE ? AND last_name LIKE ?) OR (first_name LIKE ? AND last_name LIKE ?)";
        $params = [
            "%$searchwords[0] $searchwords[1]%",
            "%$searchwords[2]%",
            "%$searchwords[0]%",
            "%$searchwords[1] $searchwords[2]%"
        ];
    }

    $q = $db->query("SELECT person_id,
                            title, first_name, last_name,
                            constituency, party,
                            left_house, house
                    FROM     member
                    WHERE    ($where)
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
                    $URL1->insert(['pid' => $last_pid]);
                    $s = '<a href="' . htmlentities($URL1->generate()) . '"><strong>';
                    $s .= htmlentities($q->field($n, 'first_name')) . ' ' . htmlentities($q->field($n, 'last_name')) . '</strong></a> (' . $former . htmlentities($q->field($n, 'constituency')) . ', ';
                } else {
                    $URL2->insert(['pid' => $last_pid]);
                    $s = '<a href="' . htmlentities($URL2->generate()) . '"><strong>';
                    $s .= htmlentities(member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency')));
                    $s .= '</strong></a> (';
                }
                $party = $q->field($n, 'party');
                if (isset($parties[$party])) {
                    $party = $parties[$party];
                }
                $s .= htmlentities($party) . ')';
                $MOREURL = new URL('search');
                $MOREURL->insert(['pid' => $last_pid, 'pop' => 1, 's' => null]);
                $s .= ' - <a href="' . htmlentities($MOREURL->generate()) . '">View recent appearances</a>';
                $members[] = $s;
            }
        }
        echo '<div id="people_results">';
        echo "<h3>Representatives matching '" . htmlentities($searchstring) . "'</h3>";
        echo '<ul>';
        echo '<li>' . implode("</li>\n\t<li>", $members) . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    // We don't display anything if there were no matches.

}


/**
 * Render links to glossary entries that match the search term.
 *
 * @param array<string, mixed> $args Search arguments.
 */
function find_glossary_items(array $args): void {

    $searchterm = $args['s'];
    $GLOSSARY = new GLOSSARY($args);

    if (isset($GLOSSARY->num_search_matches) && $GLOSSARY->num_search_matches >= 1) {

        // Got a match(es), display....
        $URL = new URL('glossary');
        $URL->insert(['gl' => ""]);

        echo '<h3>Matching glossary terms:</h3>';
        echo '<p>';
        $n = 1;
        foreach ($GLOSSARY->search_matches as $glossary_id => $term) {
            $URL->update(['gl' => $glossary_id]);
            echo '<a href="' . htmlentities($URL->generate()) . '"><strong>' . htmlentities($term['title']) . '</strong></a>';
            if ($n < $GLOSSARY->num_search_matches) {
                echo ', ';
            }
            $n++;
        }
        echo '</p>';
    }
}
