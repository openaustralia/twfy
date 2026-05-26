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
    $q = getParlDB()->query('select epobject.epobject_id from hansard,epobject
		where hansard.epobject_id=epobject.epobject_id and major=4 and section_id=0
		and hdate>(select max(hdate) from hansard where major=4) - interval 7 day
		and lower(body) = ?', $dept);
    $ids = [];
    for ($i = 0; $i < $q->rows(); $i++) {
        $ids[] = $q->field($i, 'epobject_id');
    }

    print '<h2>' . ucwords($dept) . '</h2>';
    print '<h3>Written Ministerial Statements from the past week</h3>';
    $q = getParlDB()->query('select gid,body from hansard,epobject
		where hansard.epobject_id=epobject.epobject_id and major=4 and subsection_id=0
    and section_id in (' . implode(',', $ids) . ')
		order by body');
    print '<ul>';
    for ($i = 0; $i < $q->rows(); $i++) {
        print '<li><a href="' . WEBPATH . '/wms/?id=' . fix_gid_from_db($q->field($i, 'gid')) . '">' . $q->field($i, 'body') . '</a>';
        print '</li>';
    }
    print '</ul>';

}

$PAGE->stripe_end();
$PAGE->page_end();
