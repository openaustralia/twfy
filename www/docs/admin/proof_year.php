<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReport;
use OpenAustralia\TWFY\ProofReportView;

$this_page = "admin_proof";

$years = ProofReport::years();
$held = array_column($years, 'year');
$year = (int) get_http_var('year');
if (!in_array($year, $held, true)) {
    $year = $held[0] ?? null;
}

$PAGE->page_start();
$PAGE->stripe_start();

if ($year === null) {
    $PAGE->block_start(['id' => 'proof', 'title' => 'Proof status']);
    print '<p class="text-sm text-slate-600">We hold no days yet. <a class="' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof.php') . '">All years</a></p>';
    $PAGE->block_end();
    $PAGE->stripe_end();
    $PAGE->page_end();
    exit;
}

$year_row = $years[array_search($year, $held, true)];
$months = ProofReport::months($year);
$segments = ProofReportView::segments($months);
$month = (int) get_http_var('month');
if (!in_array($month, array_column($months, 'month'), true)) {
    $month = null;
}
$all = get_http_var('all') === '1';
$page = max((int) get_http_var('page'), 1);

$toggle_on = 'bg-teal-700 !text-white border-teal-700';
$toggle_off = 'bg-white !text-slate-700 border-slate-300 hover:bg-slate-50';
$toggle_base = 'inline-block px-3 py-1.5 text-sm font-semibold border border-solid !no-underline';

$PAGE->block_start(['id' => 'proof', 'title' => 'Proof status of ' . $year]);

// Newer and older years sit either side of this one, as the list is newest first.
$index = array_search($year, $held, true);
$trail = [['Admin', '/admin/'], ['Proof', ProofReportView::link('proof.php')], [(string) $year, ProofReportView::link('proof_year.php', ['year' => $year])]];
if ($month !== null) {
    $trail[] = [ProofReportView::monthName($month), null];
}
print ProofReportView::breadcrumbs($trail);

print '<div class="flex flex-wrap items-center gap-6 text-sm font-semibold">';
if ($index > 0) {
    print '<a class="' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof_year.php', ['year' => $held[$index - 1]]) . '">' . $held[$index - 1] . ' &rarr;</a>';
}
if ($index < count($held) - 1) {
    print '<a class="' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof_year.php', ['year' => $held[$index + 1]]) . '">&larr; ' . $held[$index + 1] . '</a>';
}
print '</div>';

print '<div class="flex items-center gap-4 max-w-4xl my-4">';
print ProofReportView::bar($year_row, ProofReportView::segments([$year_row]), (string) $year);
print '<span class="w-24 text-right text-sm tabular-nums text-slate-700">' . round(ProofReport::percent($year_row['final'], $year_row['total'])) . '% final</span>';
print '<span class="w-24 text-right text-xs tabular-nums text-slate-500">' . ProofReportView::days($year_row['total']) . '</span>';
print '</div>';
print ProofReportView::legend($segments);

print '<p class="text-sm text-slate-600 max-w-3xl">Each row is a month. A day is one house\'s sitting. Choose a month to see its days. ';
print '"Last changed" is when a row last changed in our database, not when we last read it from Parliament House.</p>';

print '<div class="overflow-x-auto">';
print '<table class="w-full max-w-4xl !m-0 text-sm border-collapse">';
print '<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">';
print '<th class="px-3 py-2 font-semibold">Month</th>';
print '<th class="px-3 py-2 font-semibold">Share of days</th>';
foreach ($segments as $segment) {
    print '<th class="px-3 py-2 font-semibold text-right">' . $segment['label'] . '</th>';
}
print '<th class="px-3 py-2 font-semibold text-right">Days</th>';
print '<th class="px-3 py-2 font-semibold">Last changed</th>';
print '</tr></thead><tbody>';
foreach ($months as $row) {
    $selected = $row['month'] === $month;
    print '<tr class="border-0 border-b border-solid border-slate-200 hover:bg-teal-50' . ($selected ? ' bg-slate-100' : '') . '">';
    print '<td class="px-3 py-2 whitespace-nowrap border-0 border-l-4 border-solid ' . ($selected ? 'border-teal-700' : 'border-transparent') . '">';
    if ($row['total'] === 0) {
        print '<span class="text-slate-400">' . ProofReportView::monthName($row['month']) . '</span></td>';
    } else {
        print '<a class="font-semibold ' . ProofReportView::LINK_CLASSES . '"' . ($selected ? ' aria-current="true"' : '')
            . ' href="' . ProofReportView::link('proof_year.php', ['year' => $year, 'month' => $row['month']]) . '#days">' . ProofReportView::monthName($row['month']) . '</a></td>';
    }
    print '<td class="px-3 py-2 w-48"><div class="flex">' . ProofReportView::bar($row, $segments, ProofReportView::monthName($row['month']) . ' ' . $year) . '</div></td>';
    foreach (array_keys($segments) as $key) {
        print '<td class="px-3 py-2 text-right tabular-nums">' . $row[$key] . '</td>';
    }
    print '<td class="px-3 py-2 text-right tabular-nums">' . $row['total'] . '</td>';
    print '<td class="px-3 py-2 text-slate-600 whitespace-nowrap tabular-nums">' . htmlspecialchars($row['last_changed'] ?? '') . '</td>';
    print '</tr>';
}
print '</tbody></table></div>';

if ($month !== null) {
    $days = ProofReport::days($all, $page, $year, $month);
    $has_next = count($days) > ProofReport::PER_PAGE;
    $days = array_slice($days, 0, ProofReport::PER_PAGE);
    $params = ['year' => $year, 'month' => $month];

    print '<h3 id="days" class="mt-8 mb-2 mx-0 text-base font-semibold text-slate-900">' . ProofReportView::monthName($month) . ' ' . $year . '</h3>';

    print '<div class="flex my-4">';
    print '<a href="' . ProofReportView::link('proof_year.php', $params) . '#days" class="' . $toggle_base . ' rounded-l ' . ($all ? $toggle_off : $toggle_on) . '">Not yet known to be final</a>';
    print '<a href="' . ProofReportView::link('proof_year.php', $params + ['all' => '1']) . '#days" class="' . $toggle_base . ' rounded-r -ml-px ' . ($all ? $toggle_on : $toggle_off) . '">Every day</a>';
    print '</div>';

    print '<div class="overflow-x-auto">';
    print '<table class="w-full max-w-4xl !m-0 text-sm border-collapse">';
    print '<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">';
    print '<th class="px-3 py-2 font-semibold">Date</th>';
    print '<th class="px-3 py-2 font-semibold">House</th>';
    print '<th class="px-3 py-2 font-semibold">Status</th>';
    print '<th class="px-3 py-2 font-semibold text-right">Proof rows</th>';
    print '<th class="px-3 py-2 font-semibold text-right">Final rows</th>';
    print '<th class="px-3 py-2 font-semibold text-right">Unknown rows</th>';
    print '<th class="px-3 py-2 font-semibold">Last changed</th>';
    print '</tr></thead><tbody>';
    $all_segments = ProofReportView::segments([['mixed' => 1]]);
    foreach ($days as $day) {
        $path = $day['major'] === 1 ? '/debates/' : '/senate/';
        $status = ProofReport::status($day['proof'], $day['final'], $day['unknown']);
        $date = htmlspecialchars($day['date']);
        $pill = $all_segments[strtolower($status)]['pill'];
        print '<tr class="border-0 border-b border-solid border-slate-200 even:bg-slate-50 hover:bg-teal-50">';
        print '<td class="px-3 py-2 whitespace-nowrap"><a class="' . ProofReportView::LINK_CLASSES . '" href="' . $path . '?d=' . $date . '">' . $date . '</a></td>';
        print '<td class="px-3 py-2 text-slate-700">' . htmlspecialchars(ProofReport::HOUSES[$day['major']]) . '</td>';
        print '<td class="px-3 py-2"><span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold ' . $pill . '">' . $status . '</span></td>';
        print '<td class="px-3 py-2 text-right tabular-nums">' . $day['proof'] . '</td>';
        print '<td class="px-3 py-2 text-right tabular-nums">' . $day['final'] . '</td>';
        print '<td class="px-3 py-2 text-right tabular-nums">' . $day['unknown'] . '</td>';
        print '<td class="px-3 py-2 text-slate-600 whitespace-nowrap tabular-nums">' . htmlspecialchars($day['last_changed'] ?? '') . '</td>';
        print '</tr>';
    }
    print '</tbody></table></div>';

    print '<div class="flex gap-6 mt-4 text-sm font-semibold">';
    $day_params = $params + ['all' => $all ? '1' : null];
    if ($page > 1) {
        print '<a class="' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof_year.php', $day_params + ['page' => $page - 1]) . '#days">&larr; Newer days</a>';
    }
    if ($has_next) {
        print '<a class="' . ProofReportView::LINK_CLASSES . '" href="' . ProofReportView::link('proof_year.php', $day_params + ['page' => $page + 1]) . '#days">Older days &rarr;</a>';
    }
    print '</div>';
}

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
