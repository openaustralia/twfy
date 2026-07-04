<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/house.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

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
 * Output serialized Senate rows from a member query.
 */
function _api_getSenate_output(Builder $query): void {
    $rows = $query->get();
    $output = [];
    $last_mod = 0;

    foreach ($rows as $row) {
        /** @var \OpenAustralia\TWFY\Models\Member $row */
        $rowData = $row->toArray();
        $out = _api_getSenate_row($rowData);
        $output[] = $out;
        $time = strtotime((string) ($rowData['lastupdate'] ?? ''));
        if ($time > $last_mod) {
            $last_mod = $time;
        }
    }

    api_output($output, $last_mod);
}

/**
 *
 */
function api_getSenate_id(int $id) {

    $query = MemberModel::where('house', HOUSE::SENATE)
      ->where('person_id', $id)
      ->orderByDesc('left_house');

    if ($query->exists()) {
        _api_getSenate_output($query);
    } else {
        api_error('Unknown person ID');
    }
}
