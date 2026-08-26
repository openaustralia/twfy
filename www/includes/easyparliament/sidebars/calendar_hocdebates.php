<?php

/**
 * @file
 */

global $PAGE;

// The calendar that appears in sidebars linking to debates.
// There is a separate one for wrans (so we can have both on the same page).

// Contents varies depending on the page we're on...

if ($this_page == 'debatesday') {
    $date = get_http_var('d');
    if (preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)$#', $date, $m)) {
        $year = $m[1];
        $month = $m[2];
        $day = $m[3];
        $args = [
            'year' => $year,
            'month' => $month,
            'onday' => $date
        ];
        $title = 'Debates this month';
    } else {
        $args = [
            'months' => 1
        ];
        $title = 'Recent debates';
    }
} else {
    $args = [
        // How many recent months to show.
        'months' => 1
    ];
    $title = 'Recent debates';
}

$PAGE->block_start([
    'title' => $title,
    'class' => 'calendar-panel [&_h4]:!m-0 [&_h4]:!border-0 [&_h4]:!bg-transparent [&_h4]:!pb-3 [&_h4]:!text-base [&_h4]:!text-slate-800 [&_.calendar]:!m-0 [&_.calendar]:!h-auto [&_.calendar]:!w-full [&_.calendar]:!border-0 [&_.calendar_table]:!w-full [&_.calendar_caption]:!bg-transparent [&_.calendar_caption]:!p-0 [&_.calendar_caption]:!pb-3 [&_.calendar_caption]:!text-left [&_.calendar_caption]:!text-sm [&_.calendar_caption]:!text-slate-600 [&_.calendar_th]:!p-1 [&_.calendar_td]:!p-1 [&_.calendar_td]:!text-slate-700 [&_.calendar_td_a]:!text-teal-800 [&_.calendar_td_on]:!bg-teal-700 [&_.calendar_td_on_a]:!text-white',
]);


$LIST = new DEBATELIST();

$LIST->display('calendar', $args);


$PAGE->block_end();
