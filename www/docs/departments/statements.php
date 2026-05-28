<?php

/**
 * @file
 * Nasty way of implementing "by department" stuff with the current schema .*/

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$dept = get_http_var('dept');
$PAGE->page_start();
$PAGE->stripe_start();


if ($dept) {
    $dept = strtolower(str_replace('_', ' ', $dept));
    $q = parlDBQuery('SELECT epobject.epobject_id from hansard,epobject
		WHERE hansard.epobject_id=epobject.epobject_id AND major=4 AND section_id=0
		AND hdate>(SELECT MAX(hdate) from hansard WHERE major=4) - interval 7 day
		AND lower(body) = ?', $dept);
    $ids = [];
    for ($i = 0; $i < $q->rows(); $i++) {
        $ids[] = $q->field($i, 'epobject_id');
    }

    print '<h2>' . ucwords($dept) . '</h2>';
    print '<h3>Written Ministerial Statements from the past week</h3>';
    $q = parlDBQuery('SELECT gid,body from hansard,epobject
		WHERE hansard.epobject_id=epobject.epobject_id AND major=4 AND subsection_id=0
    AND section_id in (' . implode(',', $ids) . ')
		ORDER BY body');
    print '<ul>';
    for ($i = 0; $i < $q->rows(); $i++) {
        print '<li><a href="' . WEBPATH . '/wms/?id=' . fix_gid_from_db($q->field($i, 'gid')) . '">' . $q->field($i, 'body') . '</a>';
        print '</li>';
    }
    print '</ul>';

}

$PAGE->stripe_end();
$PAGE->page_end();
