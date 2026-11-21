<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReport;

$this_page = "admin_proof";

$all = get_http_var('all') === '1';
$page = max((int) get_http_var('page'), 1);

$days = ProofReport::days($all, $page);
$has_next = count($days) > ProofReport::PER_PAGE;
$days = array_slice($days, 0, ProofReport::PER_PAGE);

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'proof', 'title' => 'Proof status of each day']);

print '<p>Each row is a day and house we hold. "Last changed" is when a row of that day last changed in our database, ';
print 'not when we last read it from Parliament House.</p>';
print '<p>' . ($all ? 'Showing every day. ' : 'Showing days not yet known to be final. ');
print '<a href="?' . ($all ? '' : 'all=1') . '">' . ($all ? 'Show only days not yet known to be final' : 'Show every day') . '</a></p>';

print '<table><tr><th>Date</th><th>House</th><th>Status</th><th>Proof rows</th><th>Final rows</th><th>Unknown rows</th><th>Last changed</th></tr>';
foreach ($days as $day) {
    $path = $day['major'] === 1 ? '/debates/' : '/senate/';
    $link = '<a href="' . $path . '?d=' . htmlspecialchars($day['date']) . '">' . htmlspecialchars($day['date']) . '</a>';
    print '<tr><td>' . $link . '</td>';
    print '<td>' . htmlspecialchars(ProofReport::HOUSES[$day['major']]) . '</td>';
    print '<td>' . ProofReport::status($day['proof'], $day['final'], $day['unknown']) . '</td>';
    print '<td>' . $day['proof'] . '</td><td>' . $day['final'] . '</td><td>' . $day['unknown'] . '</td>';
    print '<td>' . htmlspecialchars($day['last_changed'] ?? '') . '</td></tr>';
}
print '</table>';

$query = $all ? 'all=1&amp;' : '';
if ($page > 1) {
    print '<p><a href="?' . $query . 'page=' . ($page - 1) . '">Newer days</a></p>';
}
if ($has_next) {
    print '<p><a href="?' . $query . 'page=' . ($page + 1) . '">Older days</a></p>';
}

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
