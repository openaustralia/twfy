<?php

/**
 * @file
 */

/**
 *
 */
function api_getConstituency_front() {
    ?>
    <p><big>Fetch an electoral division.</big></p>

    <h4>Arguments</h4>
    <dl>
        <dt>postcode</dt>
        <dd>Fetch the electoral division for a given postcode.</dd>
    </dl>

    <h4>Example Response</h4>
    <pre>{ name : "Macquarie" }</pre>
    <h4>Error Codes</h4>
    <p></p>

    <?php
}

/**
 *
 */
function api_getconstituency_postcode($pc) {
    $pc = preg_replace('#[^0-9]#i', '', $pc);
    if (is_postcode($pc)) {
        $constituency = postcode_to_constituency($pc);
        if ($constituency == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        }
        elseif ($constituency) {
            $output['name'] = html_entity_decode($constituency);
            api_output($output);
        }
        else {
            api_error('Unknown postcode');
        }
    }
    else {
        api_error('Invalid postcode');
    }
}
