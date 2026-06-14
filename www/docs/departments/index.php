<?php

/**
 * @file
 * Nasty way of implementing "by department" stuff with the current schema .*/

use OpenAustralia\TWFY\Models\Hansard;

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$dept = get_http_var('dept');
$PAGE->page_start();
$PAGE->stripe_start();


print '<h2>Departments</h2>';

$maxDate = Hansard::whereIn('major', [3, 4])->max('hdate');
$rows = Hansard::join('epobject', 'hansard.epobject_id', '=', 'epobject.epobject_id')
  ->whereIn('major', [3, 4])
  ->where('section_id', 0)
  ->whereRaw('hdate > ? - interval 7 day', [$maxDate])
  ->groupBy('body', 'major')
  ->orderBy('body')
  ->get(['major', 'body']);
$data = [];
foreach ($rows as $row) {
    $data[$row->body][$row->major] = true;
}

print '<p>List of departments who have had questions or statements within the past week</p>';

print '<ul>';
foreach ($data as $body => $arr) {
    $link = strtolower(str_replace(' ', '_', $body));
    print '<li>';
    print $body;
    print ' &mdash; ';
    if (isset($arr[3])) {
        print '<a href="' . $link . '/questions">Written Questions</a>';
    }
    if (count($arr) == 2) {
        print ' | ';
    }
    if (isset($arr[4])) {
        print '<a href="' . $link . '/statements">Written Ministerial Statements</a>';
    }
    print '</li>';
}
print '</ul>';

$PAGE->stripe_end();
$PAGE->page_end();
