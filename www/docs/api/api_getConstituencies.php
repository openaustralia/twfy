<?php

/**
 * @file
 */

/**
 *
 */
function api_getConstituencies_front() {
    ?>
    <p><big>Fetch a list of electoral divisions.</big></p>

    <h4>Arguments</h4>
    <p>Note only one argument can be given at present.</p>
    <dl>
        <dt>postcode (optional)</dt>
        <dd>Fetch the list of electoral divisions that are within the given postcode (there can be more than one)</dd>
        <dt>date (optional)</dt>
        <dd>Fetch the list of electoral divisions as it was on this date.</dd>
        <dt>search (optional)</dt>
        <dd>Fetch the list of electoral divisions that match this search string.</dd>
    </dl>

    <h4>Example Response</h4>
    <pre>[
                    { name : "Warringah" },
                    { name : "Lyons" },
                    { name : "Fairfax" },
                    ...
                ]</pre>

    <?php
}

/**
 *
 */
function api_getConstituencies_search($s) {
    $output = _api_getConstituencies_search($s);
    api_output($output);
}

/**
 *
 */
function _api_getConstituencies_search($s) {
    // FIXME timezones!
    $q = parlDBQuery('SELECT c_main.name from constituency, constituency AS c_main
		WHERE constituency.cons_id = c_main.cons_id
		AND c_main.main_name AND constituency.name LIKE ? AND constituency.from_date <= date(NOW())
		AND date(NOW()) <= constituency.to_date', "%$s%");
    $output = [];
    $done = [];
    for ($i = 0; $i < $q->rows(); $i++) {
        $name = html_entity_decode($q->field($i, 'name'));
        if (!in_array($name, $done)) {
            $output[] = [
                // 'id' => $q->field($i, 'cons_id'),
                'name' => $name
            ];
            $done[] = $name;
        }
    }
    return $output;
}

/**
 *
 */
function api_getConstituencies_date($date) {
    if ($parsed_date = parse_date($date)) {
        api_getConstituencies($parsed_date['iso']);
    } else {
        api_error('Invalid date format');
    }
}

/**
 *
 */
function api_getConstituencies($date = null) {
    // FIXME timezones!
    if ($date === null) {
        $q = parlDBQuery('SELECT cons_id, name from constituency WHERE main_name AND from_date <= CURRENT_DATE AND CURRENT_DATE <= to_date');
    } else {
        $q = parlDBQuery('SELECT cons_id, name from constituency WHERE main_name AND from_date <= date(?) AND date(?) <= to_date',
            $date, $date);
    }
    $output = [];
    for ($i = 0; $i < $q->rows(); $i++) {
        $output[] = [
            // 'id' => $q->field($i, 'cons_id'),
            'name' => html_entity_decode($q->field($i, 'name'))
        ];
    }
    api_output($output);
}
