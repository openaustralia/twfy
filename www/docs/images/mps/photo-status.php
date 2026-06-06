<?php

/**
 * @file
 */

include_once __DIR__ . "/../../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\Models\Member;

$DATA->set_page_metadata($this_page, 'heading', 'MPs photo status on OpenAustralia');
$PAGE->page_start();
$PAGE->stripe_start();

// Faithful port of:
// SELECT person_id, first_name, last_name, constituency, party
// FROM member
// WHERE house=1 AND left_house = (SELECT MAX(left_house) FROM member)
// ORDER BY last_name, first_name
// Done as two queries (max + select) rather than a correlated subquery.
$maxLeftHouse = Member::max('left_house');
$members = Member::where('house', 1)
  ->where('left_house', $maxLeftHouse)
  ->orderBy('last_name')
  ->orderBy('first_name')
  ->get(['person_id', 'first_name', 'last_name', 'constituency', 'party']);
$out = ['both' => '', 'small' => '', 'none' => ''];
foreach ($members as $member) {
    $p_id = $member->person_id;

    $first_name = $member->first_name;
    $last_name = $member->last_name;
    $full_name = "$first_name $last_name";

    [$dummy, $sz] = find_rep_image($p_id);
    switch ($sz) {
        case 'L':
            $out['both'] .= "$full_name, ";
          break;

        case 'S':
            $out['small'] .= "$full_name, ";
          break;

        default:
            $party = $member->party;
            $constituency = $member->constituency;
            $out['none'] .= "<li>$full_name ($party), $constituency</li>";
          break;
    }
}
print '<h3>Missing completely</h3> <ul>';
print $out['none'];
print '</ul>';
print '<h3>Large and small</h3> <p>';
print $out['both'];
print '<h3>Only small photos</h3> <p>';
print $out['small'];
$PAGE->stripe_end();
$PAGE->page_end();
