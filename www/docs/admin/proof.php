<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReport;
use OpenAustralia\TWFY\ProofReportView;

$this_page = "admin_proof";

$years = ProofReport::years();
$segments = ProofReportView::segments($years);

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'proof', 'title' => 'Proof status of each year']);

print ProofReportView::breadcrumbs([['Admin', '/admin/'], ['Proof', null]]);

print '<p class="text-sm text-slate-600 max-w-3xl">Each bar shows the share of the days we hold for a year. A day is one house\'s sitting. ';
print 'Choose a year to see its months and days.</p>';

if (!$years) {
    print '<p class="text-sm text-slate-600">We hold no days yet.</p>';
} else {
    print ProofReportView::legend($segments);

    print '<ol class="max-w-4xl !m-0 p-0 list-none">';
    foreach ($years as $row) {
        print '<li class="flex items-center gap-4 !ml-0 px-3 py-2 rounded hover:bg-slate-50">';
        if ($row['total'] === 0) {
            print '<span class="w-14 text-sm text-slate-400">' . $row['year'] . '</span>';
        } else {
            print '<a class="w-14 text-sm font-semibold ' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof_year.php', ['year' => $row['year']]) . '">' . $row['year'] . '</a>';
        }
        print ProofReportView::bar($row, $segments, (string) $row['year']);
        print '<span class="w-24 text-right text-sm tabular-nums text-slate-700">' . ($row['total'] === 0 ? '' : round(ProofReport::percent($row['final'], $row['total'])) . '% final') . '</span>';
        print '<span class="w-24 text-right text-xs tabular-nums text-slate-500">' . ProofReportView::days($row['total']) . '</span>';
        print '</li>';
    }
    print '</ol>';
}

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
