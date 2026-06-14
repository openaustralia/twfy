<?php

/**
 * @file
 * Nasty way of implementing "by department" stuff with the current schema .*/

use OpenAustralia\TWFY\Models\Hansard;

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$dept = get_http_var('dept');
$PAGE->page_start();
$PAGE->stripe_start();


if ($dept) {
    $dept = strtolower(str_replace('_', ' ', $dept));
    $maxDate = Hansard::where('major', 3)->max('hdate');
    $ids = Hansard::join('epobject', 'hansard.epobject_id', '=', 'epobject.epobject_id')
      ->where('major', 3)
      ->where('section_id', 0)
      ->whereRaw('hdate > ? - interval 7 day', [$maxDate])
      ->whereRaw('lower(body) = ?', [$dept])
      ->pluck('epobject.epobject_id')
      ->all();

    print '<h2>' . ucwords($dept) . '</h2>';
    print '<h3>Written Questions from the past week</h3>';
    $questions = Hansard::join('epobject', 'hansard.epobject_id', '=', 'epobject.epobject_id')
      ->where('major', 3)
      ->where('subsection_id', 0)
      ->whereIn('section_id', $ids)
      ->orderBy('body')
      ->get(['gid', 'body']);
    print '<ul>';
    foreach ($questions as $row) {
        print '<li><a href="' . WEBPATH . '/wrans/?id=' . fix_gid_from_db($row->gid) . '">' . $row->body . '</a>';
        print '</li>';
    }
    print '</ul>';

}

$PAGE->stripe_end();
$PAGE->page_end();
