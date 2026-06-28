<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/house.php';
include_once 'api_getMembers.php';
include_once 'api_getRepresentative.php';

/**
 *
 */
function api_getRepresentatives_front() {
    ?>
    <p><big>Fetch a list of members of the House of Representatives.</big></p>

    <h4>Arguments</h4>
    <dl>
        <dt>postcode (optional)</dt>
        <dd>Fetch the list of Representatives whose electoral division lies within the postcode (there may be more than one)
        </dd>
        <dt>date (optional)</dt>
        <dd>Fetch the list of members of the House of Representatives as it was on this date.</dd>
        <dt>party (optional)</dt>
        <dd>Fetch the list of Representatives from the given party.</dd>
        <dt>search (optional)</dt>
        <dd>Fetch the list of Representatives that match this search string in their name.</dd>
    </dl>

    <h4>Example Response</h4>
    <pre>a:5:{
            i:0; a:5:{
                s:9:"member_id"; s:1:"1";
                s:9:"person_id"; s:5:"10001";
                s:4:"name"; s:11:"Tony Abbott";
                s:5:"party"; s:13:"Liberal Party";
                s:12:"constituency"; s:9:"Warringah";
            }
            i:1; ...
        }
        </pre>
    <?php
}

/**
 *
 */
function api_getRepresentatives_postcode($pc) {
    $pc = preg_replace('#[^0-9]#i', '', $pc);
    if (is_postcode($pc)) {
        $constituency = postcode_to_constituency($pc);
        if ($constituency == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif ($constituency) {
            if (is_array($constituency)) {
                $constituencies = $constituency;
            } else {
                $constituencies = [$constituency];
            }
            $output = [];
            foreach ($constituencies as $c) {
                $output[] = _api_getRepresentative_constituency($c);
            }
            api_output($output);
        } else {
            api_error('Unknown postcode');
        }
    } else {
        api_error('Invalid postcode');
    }
}

/**
 *
 */
function api_getRepresentatives_party($s) {
    api_getMembers_party(HOUSE::REPRESENTATIVES, $s);
}

/**
 *
 */
function api_getRepresentatives_search($s) {
    api_getMembers_search(HOUSE::REPRESENTATIVES, $s);
}

/**
 *
 */
function api_getRepresentatives_date($date) {
    api_getMembers_date(HOUSE::REPRESENTATIVES, $date);
}

/**
 *
 */
function api_getRepresentatives($date = null) {
    api_getMembers(HOUSE::REPRESENTATIVES, $date);
}
