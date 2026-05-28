<?php

/**
 * @file
 * XXX Lots here copied from elsewhere... Damn you deadlines.
 */

include_once 'min-init.php';
include_once __DIR__ . '/../../includes/easyparliament/member.php';
include_once '../api/api_functions.php';

$pid = $_GET['pid'];


$q = parlDBQuery("SELECT * from member
	WHERE house=1 AND person_id = ?
	ORDER BY left_house DESC LIMIT 1", $pid);
if (!$q->rows()) {
    print '<error>Unknown ID</error>';
    exit;
}

$row = $q->row(0);
$row['full_name'] = member_full_name(
    $row['house'],
    $row['title'],
    $row['first_name'],
    $row['last_name'],
    $row['constituency']
);
if (isset($parties[$row['party']])) {
    $row['party'] = $parties[$row['party']];
}
[$image, $sz] = find_rep_image($row['person_id'], true);
if ($image) {
    $row['image'] = $image;
}

$q = parlDBQuery("SELECT position,dept FROM moffice WHERE to_date='9999-12-31'
	AND source='chgpages/selctee' AND person=?
	ORDER BY from_date DESC", $pid);
for ($i = 0; $i < $q->rows(); $i++) {
    $row['selctee'][] = prettify_office($q->field($i, 'position'), $q->field($i, 'dept'));
}

$q = parlDBQuery("SELECT data_key, data_value from personinfo
	WHERE data_key LIKE 'public\_whip%' AND person_id = ?
	ORDER BY data_key", $pid); // order so both_voted is always first...
$none = false;
$output = [];
for ($i = 0; $i < $q->rows(); $i++) {
    $key = str_replace(['public_whip_dreammp', '_distance'], '', $q->field($i, 'data_key'));
    if (preg_match('#_absent$#', $key)) {
        continue;
    }
    if ($none) {
        $none = false;
        $output[$key] = -1;
        continue;
    }
    $value = $q->field($i, 'data_value');
    if (preg_match('#_both_voted$#', $key)) {
        if ($value == 0) {
            $none = true;
        }
        continue;
    }
    $output[$key] = $value;
}
$pw = '<ul>';
display_dream_comparison(996, "a <strong>transparent Parliament</strong>");
display_dream_comparison(811, "introducing a <strong>smoking ban</strong>");
display_dream_comparison(230, "introducing <strong>ID cards</strong>", true);
display_dream_comparison(363, "introducing <strong>foundation hospitals</strong>");
display_dream_comparison(367, "introducing <strong>student top-up fees</strong>", true);
display_dream_comparison(258, "Labour's <strong>anti-terrorism laws</strong>", true);
display_dream_comparison(219, "the <strong>Iraq war</strong>", true);
display_dream_comparison(975, "investigating the <strong>Iraq war</strong>");
display_dream_comparison(984, "replacing <strong>Trident</strong>");
display_dream_comparison(358, "the <strong>hunting ban</strong>", true);
display_dream_comparison(826, "equal <strong>gay rights</strong>");

/**
 *
 */
function display_dream_comparison($id, $text, $inverse = false) {
    global $pw, $output;
    if (!array_key_exists($id, $output)) {
        return;
    }
    $score = $output[$id];
    if ($score == -1) {
        $pw .= '<li>Has never voted on';
    } else {
        if ($inverse) {
            $score = 1 - $score;
        }
        $pw .= '<li>Voted <strong>' . score_to_strongly($score) . '</strong>';
    }
    $pw .= ' ' . $text . '</li>';
}

$pw .= '</ul>';

$output = $row;
$output['pw_data'] = $pw;

$q = parlDBQuery("SELECT * from memberinfo WHERE member_id = ?
    AND data_key in ('swing_to_lose_seat_today', 'majority_in_seat')", $row['member_id']);
for ($i = 0; $i < $q->rows(); $i++) {
    $key = $q->field($i, 'data_key');
    $output[$key] = number_format($q->field($i, 'data_value'));
}

print '<twfy>' . api_output_xml($output) . '</twfy>';

/**
 *
 */
function score_to_strongly($dmpscore) {
    $dmpdesc = "unknown about";
    if ($dmpscore > 0.95 && $dmpscore <= 1.0) {
        $dmpdesc = "consistently against";
    } elseif ($dmpscore > 0.85) {
        $dmpdesc = "almost always against";
    } elseif ($dmpscore > 0.6) {
        $dmpdesc = "generally against";
    } elseif ($dmpscore > 0.4) {
        $dmpdesc = "a mixture of for and against";
    } elseif ($dmpscore > 0.15) {
        $dmpdesc = "generally for";
    } elseif ($dmpscore > 0.05) {
        $dmpdesc = "almost always for";
    } elseif ($dmpscore >= 0.0) {
        $dmpdesc = "consistently for";
    }
    return $dmpdesc;
}
