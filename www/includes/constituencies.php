<?php

/**
 * @file
 * $Id: constituencies.inc,v 1.2 2006/08/31 13:20:48 twfy-live Exp $
 */

/**
 *
 */
function normalise_constituency_name($name) {
    // HACK.
    return $name;
}

/**
 * as I don't want to do 646*2 DB queries!
 */
function normalise_constituency_names($names) {

    $q = parlDBQuery('SELECT constituency.name AS name,c_main.name AS canonical_name
		from constituency, constituency AS c_main
		WHERE constituency.cons_id = c_main.cons_id
		AND c_main.main_name AND constituency.name in ?
        AND constituency.from_date <= date(NOW())
		AND date(NOW()) <= constituency.to_date',
        array_values($names));
    $lookup = [];
    for ($i = 0; $i < $q->rows(); $i++) {
        $name = html_entity_decode($q->field($i, 'name'));
        $canonical = html_entity_decode($q->field($i, 'canonical_name'));
        $lookup[$name] = $canonical;
    }
    $output = [];
    foreach ($names AS $area_id => $name) {
        $output[$area_id] = $lookup[$name] ?? $name;
    }
    return $output;
}
