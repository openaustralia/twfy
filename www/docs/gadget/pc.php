<?php

/**
 * @file
 */

include_once 'min-init.php';
include_once __DIR__ . '/../../includes/easyparliament/member.php';

use OpenAustralia\TWFY\Models\Member;

$pc = $_GET['pc'];
$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
if (validate_postcode($pc)) {
    $constituency = postcode_to_constituency($pc);
    if ($constituency == 'CONNECTION_TIMED_OUT') {
        error('Connection timed out');
    } elseif ($constituency) {
        $pid = get_person_id($constituency);
        echo 'pid,', $pid;
    } else {
        error('Unknown postcode');
    }
} else {
    error('Invalid postcode');
}

/**
 *
 */
function error($s) {
    echo 'error,', $s;
}

/**
 *
 */
function get_person_id($c) {

    if ($c == '') {
        return false;
    }
    $n = normalise_constituency_name($c);
    if ($n) {
        $c = $n;
    }
    return Member::where('constituency', $c)
      ->where('left_reason', 'still_in_office')
      ->where('house', 1)
      ->value('person_id') ?? false;
}
