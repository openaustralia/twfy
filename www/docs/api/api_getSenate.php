<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/house.php';

/**
 *
 */
function api_getSenate_front() {
    ?>
    <p><big>Fetch a particular Senator.</big></p>

    <h4>Arguments</h4>
    <dl>
        <dt>id (required)</dt>
        <dd>If you know the person ID for the Senator you want, this will return data for that person.</dd>
    </dl>

    <?php
}

/**
 *
 */
function _api_getSenate_row($row) {
    global $parties;
    $row['full_name'] = member_full_name(
        $row['house'],
        $row['title'],
        $row['first_name'],
        $row['last_name'],
        $row['constituency']
    );
    if (isset($parties[$row['party']])) {
        $row['party'] = $parties[$row['party']];
    }
    $row = array_map('html_entity_decode', $row);
    return $row;
}

/**
 *
 */
function api_getSenate_id(int $id) {

    $q = parlDBQuery("SELECT * from member
        WHERE house = ? AND person_id = ?
        ORDER BY left_house DESC", HOUSE::SENATE, $id);
    if ($q->rows()) {
        $output = [];
        $last_mod = 0;
        for ($i = 0; $i < $q->rows(); $i++) {
            $out = _api_getSenate_row($q->row($i));
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
