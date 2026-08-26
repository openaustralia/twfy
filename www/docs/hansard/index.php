<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/init.php';

$hansardmajors = $GLOBALS['hansardmajors'] ?? [];

$number_of_debates_to_show = 6;
$number_of_wrans_to_show = 5;

$nextprevdata = [];

if (($date = get_http_var('d')) && preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
    $this_page = 'hansard_date';
    $PAGE->set_hansard_headings(['date' => $date]);
    $URL = new URL($this_page);

    $q = parlDBQuery("SELECT MIN(hdate) AS hdate FROM hansard WHERE hdate > ?", $date);
    if ($q->rows() > 0 && $q->field(0, 'hdate') != null) {
        $URL->insert(['d' => $q->field(0, 'hdate')]);
        $title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
        $nextprevdata['next'] = [
            'hdate' => $q->field(0, 'hdate'),
            'url' => $URL->generate(),
            'body' => 'Next day',
            'title' => $title
        ];
    }
    $q = parlDBQuery("SELECT MAX(hdate) AS hdate FROM hansard WHERE hdate < ?", $date);
    if ($q->rows() > 0 && $q->field(0, 'hdate') != null) {
        $URL->insert(['d' => $q->field(0, 'hdate')]);
        $title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
        $nextprevdata['prev'] = [
            'hdate' => $q->field(0, 'hdate'),
            'url' => $URL->generate(),
            'body' => 'Previous day',
            'title' => $title
        ];
    }
    $DATA->set_page_metadata($this_page, 'nextprev', $nextprevdata);
    $PAGE->page_start();
    $PAGE->stripe_start();
    include_once __DIR__ . '/../../includes/easyparliament/recess.php';
    $time = strtotime($date);
    $dayofweek = date('w', $time);
    $recess = recess_prettify(date('j', $time), date('n', $time), date('Y', $time), 1);
    if ($recess[0]) {
        print '<p>The Houses of Parliament are in ' . $recess[0] . ' at this time.</p>';
    } elseif ($dayofweek == 0 || $dayofweek == 6) {
        print '<p>The Houses of Parliament do not meet at weekends.</p>';
    } else {
        $data = [
            'date' => $date
        ];
        foreach (array_keys($hansardmajors) as $major) {
            $URL = new URL($hansardmajors[$major]['page_all']);
            $URL->insert(['d' => $date]);
            $data[$major] = ['listurl' => $URL->generate()];
        }
        major_summary($data);
    }
    $PAGE->stripe_end([
        [
            'type' => 'nextprev'
        ],
    ]);
    $PAGE->page_end();
    exit;
}

$this_page = 'hansard';
$PAGE->page_start();
// Page title will appear here.
$PAGE->stripe_start('head-1');
$message = $PAGE->recess_message();
if ($message != '') {
    print "<p><strong>$message</strong></p>\n";
}
$PAGE->stripe_end();
$PAGE->stripe_start();
?>
<section class="!mx-4 !box-border !w-auto rounded-lg border border-slate-200 border-t-4 border-t-teal-700 bg-white p-5 shadow-[0_3px_10px_rgba(0,0,0,0.08)] [&_dt]:border-b [&_dt]:border-slate-100 [&_dt]:pt-3 [&_dt]:font-semibold [&_dt]:!text-teal-800 [&_dd]:!m-0 [&_dd]:pb-3 [&_dd]:pt-1 [&_dd]:text-sm [&_dd]:text-slate-600">
<h3 class="!m-0 !mb-4 !text-xl !text-slate-800">🏛️ Busiest House of Representatives debates</h3>
<p class="mb-4 text-sm text-slate-600">The most active House debates from the last seven days.</p>
<?php
$DEBATELIST = new DEBATELIST();
$DEBATELIST->display('biggest_debates', ['days' => 7, 'num' => $number_of_debates_to_show]);

$MOREURL = new URL('debatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
<p class="!mb-0"><strong><a class="!text-teal-800 hover:!text-teal-600" href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more House debates</a></strong></p>
</section>
<?php

$PAGE->stripe_end([
    [
        'type' => 'include',
        'content' => "hocdebates_short"
    ],
    [
        'type' => 'include',
        'content' => "calendar_hocdebates"
    ]
]);

$PAGE->stripe_start();
?>
<section class="!mx-4 !box-border !w-auto rounded-lg border border-slate-200 border-t-4 border-t-teal-700 bg-white p-5 shadow-[0_3px_10px_rgba(0,0,0,0.08)] [&_dt]:border-b [&_dt]:border-slate-100 [&_dt]:pt-3 [&_dt]:font-semibold [&_dt]:!text-teal-800 [&_dd]:!m-0 [&_dd]:pb-3 [&_dd]:pt-1 [&_dd]:text-sm [&_dd]:text-slate-600">
<h3 class="!m-0 !mb-4 !text-xl !text-slate-800">🏛️ Busiest Senate debates</h3>
<p class="mb-4 text-sm text-slate-600">The most active Senate debates from the last seven days.</p>
<?php
$DEBATELIST = new LORDSDEBATELIST();
$DEBATELIST->display('biggest_debates', ['days' => 7, 'num' => $number_of_debates_to_show]);

$MOREURL = new URL('lordsdebatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
<p class="!mb-0"><strong><a class="!text-teal-800 hover:!text-teal-600" href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more Senate debates</a></strong></p>
</section>
<?php

$PAGE->stripe_end([
    [
        'type' => 'include',
        'content' => "holdebates_short"
    ],
    [
        'type' => 'include',
        'content' => "calendar_holdebates"
    ]
]);

$PAGE->page_end();
