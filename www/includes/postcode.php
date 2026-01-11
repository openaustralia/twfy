<?php

/**
 * @file
 * NOTE: This is now the master copy, the file postcode.php here:
 */

// http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
// is copied from this openaustralia.org version.

include_once INCLUDESPATH . "constituencies.php";

/**
 * Whether the form of the postcode is one or not.
 */
function is_postcode($postcode) {
  // Return preg_match("/^[A-Z]{1,2}\d[A-Z\d]? ?\d[ABD-HJLNP-UW-Z]{2}$/i", $postcode);.
  // In utility.php.
  return validate_postcode($postcode);
}

/**
 * Returns the constituency name, given uh, a postcode.
 */
function postcode_to_constituency($postcode) {
  global $last_postcode;
  global $last_postcode_value;

  // if(!defined("POSTCODE_SEARCH_DOMAIN") || !POSTCODE_SEARCH_DOMAIN) {
  // return fake_postcode($postcode);
  // }

  $postcode = canonicalise_postcode($postcode);

  if ($last_postcode == $postcode) {
    twfy_debug("TIME", "Postcode "
                          . print_r($postcode, TRUE)
                          . " looked up last time, is "
                          . print_r($last_postcode_value, TRUE));
    return $last_postcode_value;
  }

  $start = getmicrotime();
  twfy_debug_timestamp();
  $ret = postcode_to_constituency_internal($postcode);
  $duration = getmicrotime() - $start;
  twfy_debug("TIME", "Postcode "
                        . print_r($postcode, TRUE)
                        . " lookup took $duration seconds, returned "
                        . print_r($ret, TRUE));
  twfy_debug_timestamp();
  $last_postcode = $postcode;
  $last_postcode_value = $ret;
  return $ret;
}

/**
 * Map a postcode to an MP, random but deterministic.
 */
function fake_postcode($postcode) {
  $db = new ParlDB();
  $fake_cons_id = abs(crc32($postcode) % 630);
  $query = "select name from constituency where main_name and cons_id = '" . $fake_cons_id . "'";
  $q2 = $db->query($query);
  if ($q2->rows <= 0) {
    return FALSE;
  }

  return $q2->field(0, "name");
}

/**
 *
 */
function postcode_to_constituency_internal($postcode) {
  // Try and match with regexp to exclude non postcodes quickly.
  if (!is_postcode($postcode)) {
    return '';
  }

  $db = new ParlDB();

  $q = $db->query('select name from postcode_lookup where postcode = "' . mysqli_real_escape_string($db, $postcode) . '"');
  if ($q->rows == 1) {
    $name = $q->field(0, 'name');
    return $name;
  }
  elseif ($q->rows > 1) {
    for ($i = 0; $i < $q->rows; $i++) {
      $name[] = $q->field($i, 'name');
    }
    return $name;
  }
  else {
    return '';
  }
}

/**
 *
 */
function canonicalise_postcode($pc) {
  $pc = str_replace(' ', '', $pc);
  $pc = trim($pc);
  $pc = strtoupper($pc);
  $pc = preg_replace('#(\d[A-Z]{2})#', ' $1', $pc);
  return $pc;
}
