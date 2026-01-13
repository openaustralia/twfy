<?php

/**
 * @file
 */

include_once 'api_getHansard.php';

/**
 *
 */
function api_getDebates_front() {
  ?>
    <p><big>Fetch Debates.</big></p>
    <p>This includes Oral Questions.</p>
    <h4>Arguments</h4>
    <p>Note you can only supply <strong>one</strong> of the following search terms at present.</p>
    <dl>
        <dt>type (required)</dt>
        <dd>One of "representatives" or "senate".
        <dt>date</dt>
        <dd>Fetch the debates for this date.</dd>
        <dt>search</dt>
        <dd>Fetch the debates that contain this term.</dd>
        <dt>person</dt>
        <dd>Fetch the debates by a particular person ID.</dd>
        <dt>gid</dt>
        <dd>Fetch the speech or debate that matches this GID.</dd>
        <dt>order (optional, when using search or person)</dt>
        <dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering.</dd>
        <dt>page (optional, when using search or person)</dt>
        <dd>Page of results to return.</dd>
        <dt>num (optional, when using search or person)</dt>
        <dd>Number of results to return.</dd>
    </dl>

    <h4>Example Response (search)</h4>
    <pre>{
        "info" : {
            "s" : "cows section:lords",
            "results_per_page" : 20,
            "page" : 1,
            "total_results" : 24,
            "first_result" : 1
        },
        "searchdescription" : "containing the word 'cows' in Senate debates",
        "rows" : [{
            "gid" : "2009-02-11.70.21",
            "hdate" : "2009-02-11",
            "htype" : "12",
            "major" : "101",
            "section_id" : "5316",
            "subsection_id" : "5317",
            "relevance" : 99,
            "speaker_id" : "100114",
            "hpos" : "221",
            "body" : "Shut the door. I can hear the <span class=\"hi\">cows</span> coming home!",
            "listurl" : "/senate/?id=2009-02-11.66.2&amp;s=cows+section%3Alords#g70.21",
            "speaker" : {
                "member_id" : "100114",
                "title" : "",
                "first_name" : "Barnaby",
                "last_name" : "Joyce",
                "house" : "2",
                "constituency" : "Queensland",
                "party" : "National Party",
                "person_id" : "10350",
                "url" : "/senator/?m=100114"
            },
            "parent" : {
                "body" : "Appropriation (Nation Building and Jobs) Bill (No. 1) 2008-2009; Appropriation (Nation Building and Jobs) Bill (No. 2) 2008-2009; Household Stimulus Package Bill 2009; Tax Bonus for Working Australians Bill 2009; Tax Bonus for Working Australians (Consequential Amendments) Bill 2009; Commonwealth Inscribed Stock Amendment Bill 2009: In Committee"
            }
        }]
    }
    </pre>
    <?php
}

/**
 *
 */
function api_getDebates_type($t) {
  if ($t == 'representatives') {
    $list = 'DEBATE';
    $type = 'debates';
  }
  elseif ($t == 'senate') {
    $list = 'LORDSDEBATE';
    $type = 'lords';
  }
  else {
    api_error('Unknown type');
    return;
  }
  if ($d = get_http_var('date')) {
    _api_getHansard_date($list, $d);
  }
  elseif (get_http_var('search') || get_http_var('person')) {
    $s = get_http_var('search');
    $pid = get_http_var('person');
    _api_getHansard_search([
          's' => $s,
          'pid' => $pid,
          'type' => $type,
      ]);
  }
  elseif ($gid = get_http_var('gid')) {
    $redirect = _api_getHansard_gid($list, $gid);
    if (is_string($redirect)) {
      $URL = $_SERVER['REQUEST_URI'];
      $URL = str_replace($gid, $redirect, $URL);
      // header('Location: http://' . DOMAIN . $URL);
      // exit;.
    }
  }
  elseif ($y = get_http_var('year')) {
    _api_getHansard_year($list, $y);
  }
  else {
    api_error('That is not a valid search.');
  }
}

/**
 *
 */
function api_getDebates_date($d) {
  api_error('You must supply a type');
}

/**
 *
 */
function api_getDebates_search($s) {
  api_error('You must supply a type');
}

/**
 *
 */
function api_getDebates_person($p) {
  api_error('You must supply a type');
}

/**
 *
 */
function api_getDebates_gid($p) {
  api_error('You must supply a type');
}
