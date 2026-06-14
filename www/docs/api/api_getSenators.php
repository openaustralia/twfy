<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/house.php';
include_once 'api_getMembers.php';

/**
 *
 */
function api_getSenators_front() {
    ?>
    <p><big>Fetch a list of Senators.</big></p>

    <h4>Arguments</h4>
    <dl>
        <dt>date (optional)</dt>
        <dd>Fetch the list of Senators as it was on this date.</dd>
        <dt>party (optional)</dt>
        <dd>Fetch the list of Senators from the given party.</dd>
        <dt>state (optional)</dt>
        <dd>Fetch the list of Senators from the given state.<br />
            (NSW, Tasmania, WA, Queensland, Victoria, SA, NT, ACT)</dd>
        <dt>search (optional)</dt>
        <dd>Fetch the list of Senators that match this search string in their name.</dd>
    </dl>

    <h4>Example Response</h4>
    <pre>
        &lt;result&gt;
            &lt;match&gt;
                &lt;member_id&gt;100077&lt;/member_id&gt;
                &lt;person_id&gt;10214&lt;/person_id&gt;
                &lt;name&gt;John Faulkner&lt;/name&gt;
                &lt;party&gt;Australian Labor Party&lt;/party&gt;
                &lt;constituency&gt;NSW&lt;/constituency&gt;
            &lt;/match&gt;
            &lt;match&gt;
                &lt;member_id&gt;100261&lt;/member_id&gt;
                &lt;person_id&gt;10716&lt;/person_id&gt;
                &lt;name&gt;John Williams&lt;/name&gt;
                &lt;party&gt;National Party&lt;/party&gt;
                &lt;constituency&gt;NSW&lt;/constituency&gt;
            &lt;/match&gt;
            ...
        &lt;/result&gt;
        </pre>
    <?php
}

/**
 *
 */
function api_getSenators_party($s) {
    api_getMembers_party(HOUSE::SENATE, $s);
}

/**
 *
 */
function api_getSenators_state($s) {
    api_getMembers_state(HOUSE::SENATE, $s);
}

/**
 *
 */
function api_getSenators_search($s) {
    api_getMembers_search(HOUSE::SENATE, $s);
}

/**
 *
 */
function api_getSenators_date($date) {
    api_getMembers_date(HOUSE::SENATE, $date);
}

/**
 *
 */
function api_getSenators($date = null) {
    api_getMembers(HOUSE::SENATE, $date);
}
