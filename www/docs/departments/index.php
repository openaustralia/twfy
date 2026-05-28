<?php

/**
 * @file
 * Nasty way of implementing "by department" stuff with the current schema .*/

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$dept = get_http_var('dept');
$PAGE->page_start();
$PAGE->stripe_start();


print '<h2>Departments</h2>';

$q = parlDBQuery('SELECT major,body from hansard,epobject
	WHERE hansard.epobject_id=epobject.epobject_id AND major in (3,4) AND section_id=0
	AND hdate>(SELECT MAX(hdate) from hansard WHERE major in (3,4)) - interval 7 day
	GROUP BY body, major
	ORDER BY body');
$data = [];
for ($i = 0; $i < $q->rows(); $i++) {
    $body = $q->field($i, 'body');
    $major = $q->field($i, 'major');
    $data[$body][$major] = true;
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
