<?php

/**
 * @file
 */

include_once 'api_getMembers.php';

/**
 *
 */
function api_getMPs_front() {
  ?>
<p><big>Fetch a list of members of the House of Representatives.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode (optional)</dt>
<dd>Fetch the list of Representatives whose electoral division lies within the postcode (there may be more than one)</dd>
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
 * See api_getMembers.php for these shared functions .
 */
function api_getMPs_party($s) {
  api_getMembers_party(1, $s);
}

/**
 *
 */
function api_getMPs_search($s) {
  api_getMembers_search(1, $s);
}

/**
 *
 */
function api_getMPs_date($date) {
  api_getMembers_date(1, $date);
}

/**
 *
 */
function api_getMPs($date = 'now()') {
  api_getMembers(1, $date);
}
