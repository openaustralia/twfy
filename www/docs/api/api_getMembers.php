<?php

/**
 * @file
 */

include_once 'api_getRepresentative.php';

/**
 * Shared API functions for get<Members>
 */
function _api_getMembers_output($sql, ...$params) {

    $q = parlDBQuery($sql, ...$params);
    $output = [];
    $last_mod = 0;
    for ($i = 0; $i < $q->rows(); $i++) {
        $out = _api_getRepresentative_row($q->row($i));
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
    // Needed to call db->escape()
    global $parties;
    $canon_to_short = array_flip($parties);
    if (isset($canon_to_short[ucwords($s)])) {
        $s = $canon_to_short[ucwords($s)];
    }
    _api_getMembers_output('SELECT * from member
		WHERE house = ?
		AND party LIKE ? AND entered_house <= date(NOW()) AND date(NOW()) <= left_house',
        $house, "%$s%");
}

/**
 *
 */
function api_getMembers_state($house, $s) {
    // Needed to call db->escape()
    global $parties;
    $canon_to_short = array_flip($parties);
    if (isset($canon_to_short[ucwords($s)])) {
        $s = $canon_to_short[ucwords($s)];
    }
    _api_getMembers_output('SELECT * from member
                WHERE house = ?
                AND constituency LIKE ? AND entered_house <= date(NOW()) AND date(NOW()) <= left_house',
        $house, "%$s%");
}

/**
 *
 */
function api_getMembers_search($house, $s) {
    if ($house == HOUSE::SENATE) {
        _api_getMembers_output("SELECT * from member
			WHERE house = ?
			AND (first_name LIKE ?
			OR last_name LIKE ?
			OR CONCAT(first_name, ' ', last_name) LIKE ?
			OR constituency LIKE ?)
			AND entered_house <= date(NOW()) AND date(NOW()) <= left_house",
        $house, "%$s%", "%$s%", "%$s%", "%$s%");
    } else {
        _api_getMembers_output("SELECT * from member
			WHERE house = ?
			AND (
                first_name LIKE ?
                OR last_name LIKE ?
                OR CONCAT(first_name, ' ', last_name) LIKE ?
            )
			AND entered_house <= date(NOW())
            AND date(NOW()) <= left_house",
        $house, "%$s%", "%$s%", "%$s%");
    }
}

/**
 *
 */
function api_getMembers_date($house, $date) {
    if ($date = parse_date($date)) {
        api_getMembers($house, '"' . $date['iso'] . '"');
    } else {
        api_error('Invalid date format');
    }
}

/**
 *
 */
function api_getMembers($house, $date = null) {
    if ($date === null) {
        _api_getMembers_output('SELECT * from member WHERE house= ? ' .
            ' AND entered_house <= date(NOW()) AND date(NOW()) <= left_house', $house);
    } else {
        _api_getMembers_output('SELECT * from member WHERE house= ? ' .
            ' AND entered_house <= date(?) AND date(?) <= left_house', $house, $date, $date);
    }
}
