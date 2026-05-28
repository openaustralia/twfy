<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/member.php';

/**
 *
 */
function api_getMP_front() {
    ?>
    <p><big>Fetch a particular member of the House of Representatives.</big></p>

    <h4>Arguments</h4>
    <dl>
        <dt>id (optional)</dt>
        <dd>If you know the person ID for the member you want (returned from getRepresentatives OR elsewhere), this will
            return data for that person.
        </dd>
        <dt>division (optional)</dt>
        <dd>The name of an electoral division; we will try and work it out from whatever you give us. :)</dd>
        <dt>always_return (optional)</dt>
        <dd>For the division option, sets whether to always try and return a Representative, even if the seat is currently
            vacant.</dd>
        <!--
<dt>extra (optional)</dt>
<dd>Returns extra data in one or more categories, separated by commas.</dd>
-->
    </dl>

    <h4>Example Response</h4>
    <pre>
        [{
            "member_id" : "1",
            "house" : "1",
            "first_name" : "Tony",
            "last_name" : "Abbott",
            "constituency" : "Warringah",
            "party" : "Liberal Party",
            "entered_house" : "1994-03-26",
            "left_house" : "9999-12-31",
            "entered_reason" : "by_election",
            "left_reason" : "still_in_office",
            "person_id" : "10001",
            "title" : "",
            "lastupdate" : "2008-07-20 22:54:54",
            "full_name" : "Tony Abbott",
            "image" : "/images/mpsL/10001.jpg",
            "office" : [{
            "moffice_id" : "23013",
            "dept" : "",
            "position" : "Leader of the Opposition",
            "from_date" : "2009-12-08",
            "to_date" : "9999-12-31",
            "person" : "10001",
            "source" : ""
        }]
        }]
    </pre>

    <?php
}

/**
 *
 */
function _api_getMP_row($row) {
    global $parties;
    $row['full_name'] = member_full_name(
        $row['house'],
        $row['title'],
        $row['first_name'],
        $row['last_name'],
        $row['constituency']
    );
    // We need 'name' to maintain backwards compatibility due to OA-476.
    $row['name'] = $row['full_name'];
    if (isset($parties[$row['party']])) {
        $row['party'] = $parties[$row['party']];
    }
    [$image, $sz] = find_rep_image($row['person_id']);
    if ($image) {
        $row['image'] = $image;
    }

    // Ministerialships and Select Committees.

    $q = parlDBQuery('SELECT * FROM moffice WHERE to_date="9999-12-31" AND person = ? ORDER BY from_date DESC', $row['person_id']);
    for ($i = 0; $i < $q->rows(); $i++) {
        $row['office'][] = $q->row($i);
    }

    foreach ($row as $k => $r) {
        if (is_string($r)) {
            $row[$k] = html_entity_decode($r);
        }
    }
    return $row;
}

/**
 *
 */
function api_getMP_id($id) {

    $q = parlDBQuery("SELECT * from member
		WHERE house=1 AND person_id = ?
		ORDER BY left_house DESC", $id);
    if ($q->rows()) {
        $output = [];
        $last_mod = 0;

        for ($i = 0; $i < $q->rows(); $i++) {
            $out = _api_getMP_row($q->row($i));
            $output[] = $out;
            $time = strtotime($q->field($i, 'lastupdate'));
            if ($time > $last_mod) {
                $last_mod = $time;
            }
        }
        api_output($output, $last_mod);
    } else {
        api_error('Unknown person ID');
    }
}

/**
 *
 */
function api_getMP_postcode($pc) {
    $pc = preg_replace('#[^0-9]#i', '', $pc);

    if (is_postcode($pc)) {
        $constituency = postcode_to_constituency($pc);
        if ($constituency == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif ($constituency) {
            $person = _api_getMP_constituency($constituency);
            $output = $person;
            api_output($output, strtotime($output['lastupdate']));
        } else {
            api_error('Unknown postcode');
        }
    } else {
        api_error('Invalid postcode');
    }
}

/**
 *
 */
function api_getMP_constituency($constituency) {
    $person = _api_getMP_constituency($constituency);
    if ($person) {
        $output = $person;
        api_output($output, strtotime($output['lastupdate']));
    } else {
        api_error('Unknown constituency, or no MP for that constituency');
    }
}

/**
 * Very similary to MEMBER's constituency_to_person_id
 * Should all be abstracted properly :-/.
 */
function _api_getMP_constituency($constituency) {


    if ($constituency == '') {
        return false;
    }

    if ($constituency == 'Orkney ') {
        $constituency = 'Orkney &amp; Shetland';
    }

    $normalised = normalise_constituency_name($constituency);
    if ($normalised) {
        $constituency = $normalised;
    }

    $q = parlDBQuery("SELECT * FROM member
		WHERE constituency = ?
		AND left_reason = 'still_in_office' AND house=1", $constituency);
    if ($q->rows > 0) {
        return _api_getMP_row($q->row(0));
    }

    if (get_http_var('always_return')) {
        $q = parlDBQuery("SELECT * FROM member
			WHERE house=1 AND constituency = ?
			ORDER BY left_house DESC LIMIT 1", $constituency);
        if ($q->rows > 0) {
            return _api_getMP_row($q->row(0));
        }
    }

    return false;
}
