<?php

/**
 * @file
 */

include_once '../../../../phplib/mapit.php';

/**
 *
 */
function _api_getGeometry_name($name) {
    $geometry = _api_getGeometry();
    // XXX.
    $name = html_entity_decode(normalise_constituency_name($name));
    $out = [];
    foreach ($geometry['data'] as $n => $data) {
        if ($n == $name) {
            return $data;
        }
    }
    return NULL;
}

/**
 *
 */
function _api_cacheCheck($fn, $arg) {
    $cache = INCLUDESPATH . '../docs/api/cache/' . $fn;
    if (is_file($cache)) {
        return unserialize(file_get_contents($cache));
    }
    $out = call_user_func($fn, $arg);
    $fp = fopen($cache, 'w');
    if ($fp) {
        fwrite($fp, serialize($out));
        fclose($fp);
    }
    return $out;
}

/**
 *
 */
function _api_getGeometry() {
    if (!defined('OPTION_MAPIT_URL') || !OPTION_MAPIT_URL) {
        return ['data' => []];
    }

    $areas = _api_cacheCheck('mapit_get_areas_by_type', 'WMC');
    $areas_geometry = _api_cacheCheck('mapit_get_voting_areas_geometry', $areas);
    $areas_info = _api_cacheCheck('mapit_get_voting_areas_info', $areas);
    $areas_out = ['date' => date('Y-m-d'), 'data' => []];
    $names = [];
    foreach (array_keys($areas_info) as $area_id) {
        $names[$area_id] = $areas_info[$area_id]['name'];
    }
    $names = normalise_constituency_names($names);
    foreach (array_keys($areas_info) as $area_id) {
        $out = [];
        $name = $names[$area_id];
        if (count($areas_geometry[$area_id])) {
            $out['name'] = $name;
            $out['centre_lat'] = $areas_geometry[$area_id]['centre_lat'];
            $out['centre_lon'] = $areas_geometry[$area_id]['centre_lon'];
            $out['area'] = $areas_geometry[$area_id]['area'];
            $out['min_lat'] = $areas_geometry[$area_id]['min_lat'];
            $out['max_lat'] = $areas_geometry[$area_id]['max_lat'];
            $out['min_lon'] = $areas_geometry[$area_id]['min_lon'];
            $out['max_lon'] = $areas_geometry[$area_id]['max_lon'];
            $out['parts'] = $areas_geometry[$area_id]['parts'];
        }
        $areas_out['data'][$name] = $out;
    }
    return $areas_out;
}
