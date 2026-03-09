<?php

/**
 * @file
 */

include_once 'api_getConstituencies.php';

/**
 *
 */
function api_getDivisions_front() {
    api_getConstituencies_front();
}

/**
 *
 */
function api_getDivisions_postcode($pc) {
    $pc = preg_replace('#[^0-9]#i', '', $pc);
    $output = [];
    if (is_postcode($pc)) {
        $constituency = postcode_to_constituency($pc);
        if ($constituency == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        }
        elseif ($constituency) {
            if (is_array($constituency)) {
                $constituencies = $constituency;
            }
            else {
                $constituencies = [$constituency];
            }
            foreach ($constituencies as $c) {
                $output[] = ['name' => html_entity_decode($c)];
            }
        }
        else {
            api_error('Unknown postcode');
        }
    }
    else {
        api_error('Invalid postcode');
    }
    api_output($output);
}

/**
 *
 */
function api_getDivisions_search($s) {
    api_getConstituencies_search($s);
}

/**
 *
 */
function api_getDivisions_date($date) {
    api_getConstituencies_date($date);
}

/**
 *
 */
function api_getDivisions($date = 'now()') {
    api_getConstituencies($date);
}
