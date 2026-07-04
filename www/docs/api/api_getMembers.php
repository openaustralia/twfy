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
 * Scope: members active on the provided date (or today when omitted).
 */
function _api_membersActiveOnDate($house, ?string $onDate = null): Builder {
    $effectiveDate = $onDate ?? date('Y-m-d');

    return MemberModel::query()->where('house', $house)
      ->whereDate('entered_house', '<=', $effectiveDate)
      ->whereDate('left_house', '>=', $effectiveDate);
}

/**
 * Canonicalize party/state search text where a mapping exists.
 */
function _api_getMembersCanonicalSearchTerm(string $s): string {
    global $parties;

    $canon_to_short = array_flip($parties);
    $canonical = ucwords($s);
    return $canon_to_short[$canonical] ?? $s;
}

/**
 * Output current members filtered by one LIKE field.
 */
function _api_getMembersOutputByLikeField($house, string $field, string $search): void {
    _api_getMembers_output(
        _api_membersActiveOnDate($house)
          ->where($field, 'LIKE', "%$search%")
    );
}

/**
 * Apply the getMembers text search filter.
 */
function _api_getMembersApplySearchFilter(Builder $query, $house, string $s): Builder {
    $like = "%$s%";
    $nameParts = preg_split('/\s+/', trim($s));

    return $query->where(function ($q) use ($house, $like, $nameParts) {
        $q->where('first_name', 'LIKE', $like)
          ->orWhere('last_name', 'LIKE', $like);

        if (count($nameParts) >= 2) {
            $first = array_shift($nameParts);
            $last = implode(' ', $nameParts);

            $q->orWhere(function ($nameQuery) use ($first, $last) {
                $nameQuery->where('first_name', 'LIKE', "%$first%")
                  ->where('last_name', 'LIKE', "%$last%");
            });
        }

        if ($house == HOUSE::SENATE) {
            $q->orWhere('constituency', 'LIKE', $like);
        }
    });
}

/**
 *
 */
function api_getMembers_party($house, $s) {
    $s = _api_getMembersCanonicalSearchTerm($s);
    _api_getMembersOutputByLikeField($house, 'party', $s);
}

/**
 *
 */
function api_getMembers_state($house, $s) {
    $s = _api_getMembersCanonicalSearchTerm($s);
    _api_getMembersOutputByLikeField($house, 'constituency', $s);
}

/**
 *
 */
function api_getMembers_search($house, $s) {
    $query = _api_getMembersApplySearchFilter(_api_membersActiveOnDate($house), $house, $s);
    _api_getMembers_output($query);
}

/**
 *
 */
function api_getMembers_date($house, $date) {
    if ($date = parse_date($date)) {
        api_getMembers($house, $date['iso']);
    } else {
        api_error('Invalid date format');
    }
}

/**
 *
 */
function api_getMembers($house, $date = null) {
    _api_getMembers_output(_api_membersActiveOnDate($house, $date));
}
