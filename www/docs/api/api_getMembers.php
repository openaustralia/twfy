<?php

/**
 * @file
 */

include_once 'api_getRepresentative.php';

use Illuminate\Database\Eloquent\Builder;
use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 * Shared API functions for get<Members>
 */
function _api_getMembers_output(Builder $query) {

    $rows = $query->get();
    $output = [];
    $last_mod = 0;
    foreach ($rows as $row) {
        $out = _api_getRepresentative_row($row->toArray());
        $output[] = $out;
        $time = strtotime($row->lastupdate);
        if ($time > $last_mod) {
            $last_mod = $time;
        }
    }
    api_output($output, $last_mod);
}

/**
 * Scope: current members of the given house.
 */
function _api_currentMembers($house) {
    return MemberModel::where('house', $house)
      ->whereRaw('entered_house <= date(NOW())')
      ->whereRaw('date(NOW()) <= left_house');
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
    _api_getMembers_output(
        _api_currentMembers($house)
          ->where('party', 'LIKE', "%$s%")
    );
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
    _api_getMembers_output(
        _api_currentMembers($house)
          ->where('constituency', 'LIKE', "%$s%")
    );
}

/**
 *
 */
function api_getMembers_search($house, $s) {
    $query = _api_currentMembers($house)
      ->where(function ($q) use ($house, $s) {
          $q->where('first_name', 'LIKE', "%$s%")
            ->orWhere('last_name', 'LIKE', "%$s%")
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$s%"]);
          if ($house == HOUSE::SENATE) {
              $q->orWhere('constituency', 'LIKE', "%$s%");
          }
      });
    _api_getMembers_output($query);
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
        _api_getMembers_output(_api_currentMembers($house));
    } else {
        _api_getMembers_output(
            MemberModel::where('house', $house)
              ->whereRaw('entered_house <= date(?)', [$date])
              ->whereRaw('date(?) <= left_house', [$date])
        );
    }
}
