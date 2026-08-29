<?php

/**
 * @file
 * Front page. Restructured to borrow the layout of Donna's homepage mockup
 * (https://kattekrab.github.io/openausmockup.html) - hero + search, a 3-feature row,
 * a "Latest Activity in Parliament" two-column feed, and a charity/about callout at
 * the bottom (where "What's all this about?" moved to - see about_this_site_card())
 * - kept in our own navy/teal/slate palette (PR #225) rather than the mockup's blue.
 *
 * Markup lives in Plates templates under resources/views/front/ - see
 * FrontPageView.php for the plain-data-in-plain-data-out logic that feeds them
 * (mirrors HansardSpeechView.php's split for the debate transcript page). The
 * functions below are orchestration only: DB/global access and building the data
 * each template needs, no HTML of their own beyond what's still directly
 * DB-coupled (latest_activity_items()) and hasn't been worth pulling out on its own.
 */

use League\Plates\Engine;

$this_page = "home";

include_once __DIR__ . "/../includes/easyparliament/init.php";
include_once __DIR__ . "/../includes/easyparliament/member.php";
include_once __DIR__ . "/../includes/easyparliament/FrontPageView.php";

$PAGE->page_start();

// This page has no real sidebar content, so skip the (otherwise empty) sidebar column.
$PAGE->stripe_start('side', '', true);
$message = $PAGE->recess_message();
if ($message != '') {
    print '<p id="warning" class="!box-border !w-[calc(100vw-2rem)]">' . $message . '</p>';
}

$platesEngine = new Engine(__DIR__ . "/../resources/views");

/**
 * Display the homepage Hansard search form.
 */
function search_hero() {
    global $platesEngine;

    $SEARCHURL = new URL('search');
    echo $platesEngine->render('front/search-hero', [
        'searchUrl' => $SEARCHURL->generate(),
        'keyword' => get_http_var('keyword'),
        'popularSearchesLabel' => popular_searches_label(),
    ]);
}

/**
 * FrontPageView::popularSearchesLabel()'s input - the SEARCHLOG DB lookup itself,
 * kept here rather than in FrontPageView.php since that class is DB-free by design.
 */
function popular_searches_label() {
    global $SEARCHLOG;
    return FrontPageView::popularSearchesLabel($SEARCHLOG->popular_recent(10));
}

/**
 * The mockup's 3-icon feature row ("Track Your Reps" / "Read the Debates" / "Get
 * Free Email Alerts") - same three things the old "At OpenAustralia.org you can:"
 * bullet list offered (find-your-MP, search, email alerts), just laid out as the
 * mockup's icon-circle columns instead of a plain <ol>. The postcode-lookup and
 * logged-in/keyword-aware branching are unchanged, still resolved here (mp_block()
 * below) - only the surrounding markup moved to resources/views/front/.
 */
function feature_row() {
    global $platesEngine;

    $HANSARDURL = new URL('hansard');
    $keyword = get_http_var('keyword');
    echo $platesEngine->render('front/feature-row', [
        'mpBlock' => mp_block(),
        'hansardUrl' => $HANSARDURL->generate('none'),
        'emailAlertUrl' => FrontPageView::emailAlertUrl($keyword),
        'emailAlertText' => FrontPageView::emailAlertText($keyword),
    ]);
}

/**
 * Resolves $THEUSER's constituency (a real DB lookup via MEMBER, when set) into the
 * plain array FrontPageView::mpBlock() expects - null when there's no MP to show
 * (no constituency set, or the resolved MEMBER wasn't valid).
 */
function mp_block() {
    global $THEUSER, $MPURL;

    $member = null;
    if ($THEUSER->constituency_is_set()) {
        // (We don't allow the user to search for a postcode if they
        // already have one set in their prefs.)
        $MEMBER = new MEMBER(['constituency' => $THEUSER->constituency()]);
        if ($MEMBER->valid) {
            $left_house = $MEMBER->left_house();
            $member = [
                'first_name' => $MEMBER->first_name(),
                'last_name' => $MEMBER->last_name(),
                'still_in_house' => $left_house[1]['date'] == '9999-12-31',
            ];
        }
    }

    $CHANGEURL = new URL('userchangepc');
    return FrontPageView::mpBlock($member, $MPURL->generate(), $CHANGEURL->generate());
}

/**
 * "Latest Activity in Parliament" - the mockup's two-column (House/Senate) feed of
 * recent debates, each with its first speaker and time, not just major_summary()'s
 * (utility.php) plain list of section titles/links. major_summary() is shared by
 * mobile.php and hansard/index.php too, so left as-is there; this runs its own small
 * query instead of changing what those pages get.
 */
function latest_activity() {
    ?>
    <?php
    // max-w-5xl mx-auto: the mockup caps this section at its own container class
    // (responsive, ~1280px at this width) rather than letting it run the full content
    // width the way this section did before - matches the cap already used on the
    // feature row above and keeps the two columns from stretching so wide the cards
    // read as sparse.
    ?>
    <section class="mx-4 mb-12 md:mx-8">
        <h2 class="mb-1 text-center text-3xl font-bold text-slate-900">Latest Activity in Parliament</h2>
        <p class="mb-8 text-center text-slate-600">Recent debates from the House and the Senate.</p>
        <div class="mx-auto grid max-w-5xl grid-cols-1 gap-8 md:grid-cols-2">
            <?php
            // text-green-700/text-red-700: the House and Senate chambers' own actual
            // colours (green benches, red benches) - a real, recognisable Australian
            // parliamentary convention, not an arbitrary choice.
            ?>
            <?php latest_activity_column(1, 'House of Representatives', 'text-green-700'); ?>
            <?php latest_activity_column(101, 'Senate', 'text-red-700'); ?>
        </div>
    </section>
    <?php
}

/**
 * One "Latest Activity" column for a single hansard major (1 = House, 101 = Senate -
 * the two this fork actually parses, same pair the debate transcript page's own
 * $usePlatesTemplate check uses).
 */
function latest_activity_column($major, $chamberName, $iconColorClass) {
    global $hansardmajors, $platesEngine;

    $LIST = $major == 101 ? new LORDSDEBATELIST() : new DEBATELIST();
    $recent = $LIST->most_recent_day();
    if (!isset($recent['hdate'])) {
        return;
    }

    $items = latest_activity_items($major, $recent['hdate'], 5);
    $DAYURL = new URL($hansardmajors[$major]['page_all']);
    $DAYURL->insert(['d' => $recent['hdate']]);

    echo $platesEngine->render('front/latest-activity-column', [
        'chamberName' => $chamberName,
        'iconColorClass' => $iconColorClass,
        'items' => $items,
        'dayUrl' => $DAYURL->generate('none'),
        'viewAllLabel' => $major == 101 ? 'the Senate' : 'the House',
    ]);
}

/**
 * The $limit most recent debate sections for one hansard major on one date, each
 * with its first speaker and time. One extra small query per section (fine at this
 * $limit) - major_summary() (utility.php) only ever fetches title/gid, since none of
 * its other callers need a speaker name.
 */
function latest_activity_items($major, $date, $limit) {
    global $hansardmajors;

    $items = [];
    $q = parlDBQuery('SELECT hansard.epobject_id, body, gid FROM hansard, epobject
			WHERE hansard.epobject_id = epobject.epobject_id AND section_id=0
			AND hdate="' . $date . '"
			AND major=' . intval($major) . '
			ORDER BY hpos DESC LIMIT ' . intval($limit));

    $LISTURL = new URL($hansardmajors[$major]['page_all']);

    for ($i = 0; $i < $q->rows(); $i++) {
        $section_epobject_id = $q->field($i, 'epobject_id');
        $gid = fix_gid_from_db($q->field($i, 'gid'));

        $speakerQ = parlDBQuery('SELECT hansard.htime, member.title, member.first_name, member.last_name
				FROM hansard, member
				WHERE hansard.speaker_id = member.member_id
				AND hansard.section_id=' . intval($section_epobject_id) . '
				AND hansard.htype=12
				ORDER BY hansard.hpos LIMIT 1');

        $speaker = '';
        $when = format_date($date, SHORTDATEFORMAT);
        if ($speakerQ->rows()) {
            $speaker = ucfirst(member_full_name(1, $speakerQ->field(0, 'title'), $speakerQ->field(0, 'first_name'), $speakerQ->field(0, 'last_name'), ''));
            $htime = $speakerQ->field(0, 'htime');
            if ($htime && $htime != '00:00:00') {
                $when .= ', ' . format_time($htime, TIMEFORMAT);
            }
        }

        $URL = clone $LISTURL;
        $URL->insert(['id' => $gid]);
        $items[] = [
            'title' => $q->field($i, 'body'),
            'speaker' => $speaker,
            'when' => $when,
            'url' => $URL->generate('none'),
        ];
    }

    return $items;
}

/**
 * "We're a small charity with a big mission." - the mockup's own bottom-of-page
 * charity callout, using the content of sidebars/whatisthissite.php's shared khaki
 * "What's all this about?" block (also used on 404.php, index-election.php,
 * gadget/index.php and mobile.php, so left untouched there - see the note on
 * latest_activity() above: duplicate short, static content into new-design markup
 * rather than restyle a template several unrelated pages still rely on) - this site's
 * donate/about messaging was already basically that card, just under a different
 * heading.
 */
function about_this_site_card() {
    global $platesEngine;

    $URL = new URL('about');
    echo $platesEngine->render('front/about-this-site', ['aboutUrl' => $URL->generate()]);
}

$MPURL = new URL('yourmp');

search_hero();
feature_row();
latest_activity();
about_this_site_card();

$PAGE->stripe_end();
$PAGE->page_end();
