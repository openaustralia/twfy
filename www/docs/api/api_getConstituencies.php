<?php

/**
 * @file
 */

include_once 'api_getGeometry.php';

/**
 *
 */
function _api_getConstituencies_search($s) {
    $db = new ParlDB();
    $q = $db->query('select c_main.name from constituency, constituency as c_main
		where constituency.cons_id = c_main.cons_id
		and c_main.main_name and constituency.name like ? and constituency.from_date <= date(now())
		and date(now()) <= constituency.to_date', "%$s%");
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
function _api_getConstituencies_latitude($lat, $lon, $d) {
    $geometry = _api_getGeometry();
    $out = [];
    foreach ($geometry['data'] as $name => $data) {
        if (!isset($data['centre_lat']) || !isset($data['centre_lon'])) {
            continue;
        }
        $distance = RADIUS_OF_EARTH * acos(
            sin(deg2rad($lat)) * sin(deg2rad($data['centre_lat']))
            + cos(deg2rad($lat)) * cos(deg2rad($data['centre_lat']))
            * cos(deg2rad($lon - $data['centre_lon']))
        );
        if (
            deg2rad($data['centre_lat']) > deg2rad($lat) - ($d / RADIUS_OF_EARTH)
            && deg2rad($data['centre_lat']) < deg2rad($lat) + ($d / RADIUS_OF_EARTH)
            // Case where search pt is near pole.
            && (abs(deg2rad($lat)) + ($d / RADIUS_OF_EARTH) > M_PI_2
                || _api_angle_between(deg2rad($data['centre_lon']), deg2rad($lon))
                < $d / (RADIUS_OF_EARTH * cos(deg2rad($lat + $d / RADIUS_OF_EARTH))))
            && $distance < $d
        ) {
            $out[] = array_merge(
                $data,
                ['distance' => $distance, 'name' => $name]
            );
        }
    }
    usort($out, function ($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });
    return $out;
}

/**
 * _api_angle_between A1 A2
 * Given two angles A1 and A2 on a circle expressed in radians, return the
 * smallest angle between them.
 */
function _api_angle_between($a1, $a2) {
    if (abs($a1 - $a2) > M_PI) {
        return 2 * M_PI - abs($a1 - $a2);
    }
    return abs($a1 - $a2);
}
