<?php

/**
 * @file
 */

include_once 'api_getMP.php';

/**
 * Shared API functions for get<Members>
 */
function _api_getMembers_output($sql) {
    $db = new ParlDB();
    $q = $db->query($sql);
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
}

/**
 *
 */
function api_getMembers_party($house, $s) {
    global $parties;
    $canon_to_short = array_flip($parties);
    if (isset($canon_to_short[ucwords($s)])) {
        $s = $canon_to_short[ucwords($s)];
    }
    _api_getMembers_output('select * from member
		where house = ' . mysqli_real_escape_string($db, $house) . '
		and party like "%' . mysqli_real_escape_string($db, $s) .
        '%" and entered_house <= date(now()) and date(now()) <= left_house');
}

/**
 *
 */
function api_getMembers_state($house, $s) {
    global $parties;
    $canon_to_short = array_flip($parties);
    if (isset($canon_to_short[ucwords($s)])) {
        $s = $canon_to_short[ucwords($s)];
    }
    _api_getMembers_output('select * from member
                where house = ' . mysqli_real_escape_string($db, $house) . '
                and constituency like "%' . mysqli_real_escape_string($db, $s) .
        '%" and entered_house <= date(now()) and date(now()) <= left_house');
}

/**
 *
 */
function api_getMembers_search($house, $s) {
    $sq = mysqli_real_escape_string($db, $s);
    _api_getMembers_output('select * from member
		where house = ' . mysqli_real_escape_string($db, $house) . "
		and (first_name like '%$sq%'
		or last_name like '%$sq%'
		or concat(first_name,' ',last_name) like '%$sq%'"
        . ($house == 2 ? " or constituency like '%$sq%'" : '')
        . ") and entered_house <= date(now()) and date(now()) <= left_house");
}

/**
 *
 */
function api_getMembers_date($house, $date) {
    if ($date = parse_date($date)) {
        api_getMembers($house, '"' . $date['iso'] . '"');
    }
    else {
        api_error('Invalid date format');
    }
}

/**
 *
 */
function api_getMembers($house, $date = 'now()') {
    _api_getMembers_output('select * from member where house=' . mysqli_real_escape_string($db, $house) .
        ' AND entered_house <= date(' . $date . ') and date(' . $date . ') <= left_house');
}
