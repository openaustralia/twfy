<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReport;

$this_page = "admin_proof";

$years = ProofReport::years();
$year = (int) get_http_var('year');
if (!in_array($year, array_column($years, 'year'), true)) {
    $year = $years[0]['year'] ?? null;
}
$all = get_http_var('all') === '1';
$page = max((int) get_http_var('page'), 1);

$days = ProofReport::days($all, $page, $year);
$has_next = count($days) > ProofReport::PER_PAGE;
$days = array_slice($days, 0, ProofReport::PER_PAGE);

// The segments of each year's bar, in the order they are drawn. The colours are checked for colour-blind separation.
$segments = [
    'final' => ['label' => 'Final', 'bar' => 'bg-[#0d9488]', 'pill' => 'bg-teal-100 text-teal-800'],
    'proof' => ['label' => 'Proof', 'bar' => 'bg-[#d97706]', 'pill' => 'bg-amber-100 text-amber-800'],
    'mixed' => ['label' => 'Mixed', 'bar' => 'bg-[#e11d48]', 'pill' => 'bg-rose-100 text-rose-800'],
    'unknown' => ['label' => 'Unknown', 'bar' => 'bg-[#7c6fd0]', 'pill' => 'bg-violet-100 text-violet-800'],
];
$has_mixed = (bool) array_filter($years, fn (array $row): bool => $row['mixed'] > 0);
if (!$has_mixed) {
    unset($segments['mixed']);
}

$link = fn (array $params): string => '?' . htmlspecialchars(http_build_query(array_filter($params, fn ($value): bool => $value !== null && $value !== '')));
$link_classes = '!text-teal-700 !no-underline hover:!underline';
$toggle_on = 'bg-teal-700 !text-white border-teal-700';
$toggle_off = 'bg-white !text-slate-700 border-slate-300 hover:bg-slate-50';
$toggle_base = 'inline-block px-3 py-1.5 text-sm font-semibold border border-solid !no-underline';

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'proof', 'title' => 'Proof status of each day']);

print '<p class="text-sm text-slate-600 max-w-3xl">Each bar shows the share of the days we hold for a year. A day is one house\'s sitting. ';
print 'Choose a year to see its days below.</p>';

if (!$years) {
    print '<p class="text-sm text-slate-600">We hold no days yet.</p>';
}
else {
    print '<ul class="flex flex-wrap gap-x-5 gap-y-1 my-4 p-0 list-none text-sm text-slate-700">';
    foreach ($segments as $segment) {
        print '<li class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm ' . $segment['bar'] . '"></span>' . $segment['label'] . '</li>';
    }
    print '</ul>';

    print '<ol class="max-w-4xl !m-0 p-0 list-none">';
    foreach ($years as $row) {
        $selected = $row['year'] === $year;
        $parts = [];
        $bar = '';
        foreach ($segments as $key => $segment) {
            if ($row[$key] === 0) {
                continue;
            }
            $percent = ProofReport::percent($row[$key], $row['total']);
            $description = $segment['label'] . ': ' . number_format($row[$key]) . ($row[$key] === 1 ? ' day (' : ' days (') . round($percent) . '%)';
            $parts[] = $description;
            $bar .= '<div class="' . $segment['bar'] . '" style="width:' . $percent . '%;min-width:3px" title="' . htmlspecialchars($description) . '"></div>';
        }
        $label = $row['year'] . ': ' . implode(', ', $parts);
        print '<li class="flex items-center gap-4 !ml-0 px-3 py-2 rounded border-2 border-solid ' . ($selected ? 'border-teal-700 bg-slate-50' : 'border-transparent') . '">';
        print '<a class="w-14 text-sm font-semibold ' . $link_classes . '"' . ($selected ? ' aria-current="true"' : '')
            . ' href="' . $link(['year' => $row['year'], 'all' => $all ? '1' : null]) . '">' . $row['year'] . '</a>';
        print '<div class="flex flex-1 gap-0.5 h-3 overflow-hidden rounded bg-white" role="img" aria-label="' . htmlspecialchars($label) . '">' . $bar . '</div>';
        print '<span class="w-24 text-right text-sm tabular-nums text-slate-700">' . round(ProofReport::percent($row['final'], $row['total'])) . '% final</span>';
        print '<span class="w-24 text-right text-xs tabular-nums text-slate-500">' . number_format($row['total']) . ($row['total'] === 1 ? ' day' : ' days') . '</span>';
        print '</li>';
    }
    print '</ol>';

    print '<h3 class="mt-8 mb-2 mx-0 text-base font-semibold text-slate-900">' . $year . '</h3>';
    print '<p class="text-sm text-slate-600 max-w-3xl">"Last changed" is when a row of that day last changed in our database, not when we last read it from Parliament House.</p>';

    print '<div class="flex my-4">';
    print '<a href="' . $link(['year' => $year]) . '" class="' . $toggle_base . ' rounded-l ' . ($all ? $toggle_off : $toggle_on) . '">Not yet known to be final</a>';
    print '<a href="' . $link(['year' => $year, 'all' => '1']) . '" class="' . $toggle_base . ' rounded-r -ml-px ' . ($all ? $toggle_on : $toggle_off) . '">Every day</a>';
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
    foreach ($days as $day) {
        $path = $day['major'] === 1 ? '/debates/' : '/senate/';
        $status = ProofReport::status($day['proof'], $day['final'], $day['unknown']);
        $date = htmlspecialchars($day['date']);
        $pill = $segments[strtolower($status)]['pill'] ?? 'bg-rose-100 text-rose-800';
        print '<tr class="border-0 border-b border-solid border-slate-200 even:bg-slate-50 hover:bg-teal-50">';
        print '<td class="px-3 py-2 whitespace-nowrap"><a class="' . $link_classes . '" href="' . $path . '?d=' . $date . '">' . $date . '</a></td>';
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
    if ($page > 1) {
        print '<a class="' . $link_classes . '" href="' . $link(['year' => $year, 'all' => $all ? '1' : null, 'page' => $page - 1]) . '">&larr; Newer days</a>';
    }
    if ($has_next) {
        print '<a class="' . $link_classes . '" href="' . $link(['year' => $year, 'all' => $all ? '1' : null, 'page' => $page + 1]) . '">Older days &rarr;</a>';
    }
    print '</div>';
}

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
